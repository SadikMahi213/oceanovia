/**
 * The camera scanner for the product search.
 *
 * The camera is only requested when the operator asks for it, never when the
 * page loads. A detected barcode is put into the existing search field and
 * the existing search form is submitted once; the camera is released before
 * that happens, so the light of the camera does not stay on.
 *
 * The browser only exposes the camera API in a secure context, so the reason
 * the camera is not available has to be named: a page that is served over
 * plain HTTP has no camera at all, and that is not the browser's fault.
 */

/**
 * The messages for the ways the camera can be unavailable.
 *
 * Every one of them keeps the manual way of entering a barcode open, because
 * the scanner is an addition to the search field, never a replacement.
 */
export const cameraMessages = {
    insecure:
        'This page is not served over HTTPS, and the browser only allows camera access on a secure connection. Open the site over HTTPS to scan a barcode, or type the barcode into the search field.',
    unsupported:
        'This browser does not support camera access. A current version of Chrome, Edge, Firefox or Safari can scan a barcode, or type it into the search field.',
    permission:
        'Camera access was refused. Allow the camera for this site in the browser and try again, or type the barcode into the search field.',
    noCamera:
        'No camera was found on this device. Type the barcode into the search field instead.',
    inUse:
        'The camera could not be started because it is in use by another application. Close it and try again, or type the barcode into the search field.',
    failed:
        'The camera could not be started. Type the barcode into the search field instead.',
};

/**
 * Why the camera cannot be used at all, or null when it can.
 *
 * The order matters: an insecure page is reported as such, because that is
 * what the operator has to change. Only a secure page without the camera API
 * is a browser that cannot do this.
 *
 * @param {object} context  the window the page runs in
 * @returns {string|null} the key of the message
 */
export function cameraAccessProblem(context) {
    if (! context || ! context.isSecureContext) {
        return 'insecure';
    }

    const devices = context.navigator ? context.navigator.mediaDevices : null;

    if (! devices || typeof devices.getUserMedia !== 'function') {
        return 'unsupported';
    }

    return null;
}

/**
 * The name of the failure the camera reported.
 *
 * The scanner library reports its failures as plain strings, so the name of
 * the browser error has to be read out of the message. An error object is
 * read directly.
 *
 * @param {object|string} error  the error the camera or the scanner reported
 * @returns {string} the name of the error, or an empty string
 */
export function cameraErrorName(error) {
    if (! error) {
        return '';
    }

    if (typeof error === 'string') {
        if (error.includes('Camera access is only supported in secure context')) {
            return 'InsecureContextError';
        }

        if (error.includes("doesn't support navigator.mediaDevices")
            || error.includes('Camera streaming not supported by the browser')) {
            return 'NotSupportedError';
        }

        if (error.includes('Unable to query supported devices')) {
            return 'NotFoundError';
        }

        const match = error.match(
            /\b(NotAllowedError|PermissionDeniedError|SecurityError|NotFoundError|DevicesNotFoundError|NotReadableError|TrackStartError|AbortError|OverconstrainedError)\b/,
        );

        return match ? match[1] : '';
    }

    return typeof error.name === 'string' ? error.name : '';
}

/**
 * Temporary development diagnostics for camera access failures.
 *
 * Captures the page environment and the actual error so the root cause of a
 * camera-start failure can be identified without guessing at browser state.
 * Only runs in secure localhost-like contexts; never logs secrets.
 *
 * @param {object|string} error  the error the camera or the scanner reported
 * @param {object} context  the window the page runs in
 */
export function cameraDiagnostics(error, context) {
    if (! context || ! context.isSecureContext) {
        return;
    }

    const info = {
        secureContext: context.isSecureContext,
        inIframe: context.self !== context.top,
        mediaDevices: !!(
            context.navigator && context.navigator.mediaDevices
        ),
        getUserMedia: !!(
            context.navigator &&
            context.navigator.mediaDevices &&
            typeof context.navigator.mediaDevices.getUserMedia === 'function'
        ),
        errorName: cameraErrorName(error),
        errorMessage: typeof error === 'string'
            ? error.trim()
            : (error && typeof error.message === 'string' ? error.message.trim() : ''),
        constraints: { facingMode: 'environment', fps: 10, qrbox: { width: 250, height: 160 }, aspectRatio: 1.6 },
    };

    if (info.errorName) {
        console.groupCollapsed('[barcode-scanner] camera diagnostics');
        console.log('secureContext:', info.secureContext);
        console.log('inIframe:', info.inIframe);
        console.log('mediaDevices:', info.mediaDevices);
        console.log('getUserMedia:', info.getUserMedia);
        console.log('errorName:', info.errorName);
        console.log('errorMessage:', info.errorMessage);
        console.log('constraints:', info.constraints);
        console.groupEnd();
    }
}

/**
 * The message for an error the camera reported while it was starting.
 *
 * @param {object|string} error  the error the browser or the scanner reported
 * @returns {string}
 */
export function cameraErrorMessage(error) {
    const name = cameraErrorName(error);

    if (name === 'InsecureContextError') {
        return cameraMessages.insecure;
    }

    if (name === 'NotSupportedError') {
        return cameraMessages.unsupported;
    }

    if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
        return cameraMessages.permission;
    }

    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        return cameraMessages.noCamera;
    }

    if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'AbortError') {
        return cameraMessages.inUse;
    }

    // Anything else is a failure of the camera or of the scanner itself. The
    // reason is kept when it is short enough to be read.
    const reason = typeof error === 'string'
        ? error.trim()
        : (error && typeof error.message === 'string' ? error.message.trim() : '');

    if (reason && reason.length <= 120) {
        return cameraMessages.failed.replace(
            'The camera could not be started.',
            `The camera could not be started: ${reason}`,
        );
    }

    return cameraMessages.failed;
}

/**
 * Only one scanner may run at a time.
 *
 * A second scanner would fight over the same camera, so the module keeps
 * track of the running one and refuses to start another.
 */
let activeScanner = null;

/**
 * The camera scanner for a product search field.
 *
 * @param {string} target  the selector of the search field to fill
 * @param {string} readerId  the id of the element the camera renders into
 */
export function barcodeScanner(target, readerId) {
    return {
        show: false,
        error: '',
        status: '',
        scanner: null,
        starting: false,
        handled: false,
        submitted: false,
        $trigger: null,

        init() {
            // Leaving the page releases the camera as well.
            window.addEventListener('pagehide', () => this.stop());
        },

        /**
         * The view is going away: the camera has to go with it.
         */
        destroy() {
            this.stop();
        },

        async open(event = {}) {
            // The camera is already running for this field: opening it again
            // must not reset the guards that keep a second scan and a second
            // submission from happening.
            if (this.starting || (this.show && this.scanner)) {
                return;
            }

            // Remember the trigger so focus can be restored when the modal
            // closes. Supports programmatic calls without an event.
            this.$trigger = event.currentTarget || null;

            this.error = '';
            this.status = '';
            this.handled = false;
            this.submitted = false;
            this.show = true;

            // The reader needs a size to render the camera into, so the
            // dialog has to be on screen before the camera starts.
            await this.$nextTick();
            await new Promise((resolve) => setTimeout(resolve, 60));

            this.starting = true;

            try {
                await this.start();
            } finally {
                this.starting = false;
            }

            // Move keyboard focus into the modal: focus the close button so
            // the operator can dismiss it without a mouse.
            setTimeout(() => {
                const closeButton = document.querySelector('[aria-label="Close the scanner"]');
                if (closeButton && typeof closeButton.focus === 'function') {
                    closeButton.focus();
                }
            }, 100);
        },

        async start() {
            // The reason the camera is not there is named before anything is
            // requested from the browser.
            const problem = cameraAccessProblem(window);

            if (problem) {
                this.error = cameraMessages[problem];

                return;
            }

            if (activeScanner) {
                this.error = cameraMessages.inUse;

                return;
            }

            this.status = 'Starting the camera…';

            // The scanner library is only fetched when it is used, so a page
            // that is never scanned does not pay for it.
            let scanner;

            try {
                const { Html5Qrcode, Html5QrcodeSupportedFormats } = await import('html5-qrcode');

                const formats = [
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.EAN_13,
                ];

                scanner = new Html5Qrcode(readerId, {
                    formatsToSupport: formats,
                    verbose: false,
                });
            } catch (error) {
                // The scanner itself could not be loaded.
                this.fail(error);

                return;
            }

            this.scanner = scanner;

            activeScanner = this.scanner;

            const configuration = { fps: 10, qrbox: { width: 250, height: 160 }, aspectRatio: 1.6 };
            const onScan = (decodedText) => this.onScan(decodedText);
            // A frame without a barcode is not a failure, it is the normal
            // case while the camera looks at the label.
            const onFrame = () => {};

            try {
                await scanner.start(
                    // The rear camera of a phone is the one that faces the
                    // barcode.
                    { facingMode: 'environment' },
                    configuration,
                    onScan,
                    onFrame,
                );
            } catch (error) {
                if (cameraErrorName(error) !== 'OverconstrainedError') {
                    this.fail(error);

                    return;
                }

                // The device has no rear camera. The camera it does have is
                // used instead of giving up.
                try {
                    await scanner.start(true, configuration, onScan, onFrame);
                } catch (fallbackError) {
                    this.fail(fallbackError);

                    return;
                }
            }

            this.status = 'Looking for a barcode…';
        },

        /**
         * Give up on the camera and say why.
         */
        fail(error) {
            activeScanner = null;
            this.scanner = null;
            this.status = '';
            this.error = cameraErrorMessage(error);

            // Temporary development diagnostics: capture the environment and
            // the actual error so the root cause of a camera-start failure can
            // be identified. Disabled on non-secure contexts.
            cameraDiagnostics(error, window);
        },

        /**
         * The first valid code wins. The decoder reports the same code again
         * for every frame it sees, so a second report is ignored.
         */
        onScan(decodedText) {
            if (this.handled) {
                return;
            }

            const value = String(decodedText || '').trim();

            if (value === '') {
                return;
            }

            this.handled = true;

            this.stop().finally(() => this.applyToSearch(value));
        },

        /**
         * Put the code into the existing search field and run the existing
         * search.
         */
        applyToSearch(value) {
            const input = document.querySelector(target);

            if (input) {
                input.value = value;

                // The search field is bound to the live search, so the change
                // has to be announced the way a typed character is.
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            this.show = false;

            if (this.submitted || ! input) {
                return;
            }

            this.submitted = true;

            const form = input.closest('form');

            if (! form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        },

        /**
         * Stop the scanner and release the camera.
         */
        async stop() {
            const scanner = this.scanner;
            this.scanner = null;

            if (activeScanner === scanner) {
                activeScanner = null;
            }

            if (scanner) {
                try {
                    await scanner.stop();
                } catch (error) {
                    // The camera may already be gone, which is not a problem.
                }

                try {
                    scanner.clear();
                } catch (error) {
                    // The reader element may already be removed.
                }
            }

            this.stopMediaTracks();
        },

        /**
         * Stop every camera track that is still running.
         *
         * The scanner stops the camera itself, but a track that outlived it
         * would keep the light of the camera on, so the tracks are stopped
         * here as well.
         */
        stopMediaTracks() {
            const reader = document.getElementById(readerId);

            if (! reader || typeof reader.querySelector !== 'function') {
                return;
            }

            const video = reader.querySelector('video');
            const stream = video ? video.srcObject : null;

            if (! stream || typeof stream.getTracks !== 'function') {
                return;
            }

            stream.getTracks().forEach((track) => {
                try {
                    track.stop();
                } catch (error) {
                    // A track that is already stopped is not a problem.
                }
            });
        },

        async close() {
            // Remember the trigger before the modal disappears so focus can be
            // restored to it after the camera is released.
            const trigger = this.$trigger;
            this.show = false;

            await this.stop();

            this.$trigger = null;

            // Restore focus to the element that opened the modal.
            if (trigger && typeof trigger.focus === 'function') {
                trigger.focus();
            }
        },
    };
}
