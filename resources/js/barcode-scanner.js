import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

/**
 * The barcode formats the scanner looks for.
 *
 * These are the retail formats a product carries: UPC-A, UPC-E, EAN-8 and
 * EAN-13. A code of another format is not decoded, so the scanner does not
 * report something that is not a product barcode.
 */
const SUPPORTED_FORMATS = [
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.EAN_13,
];

/**
 * Only one scanner may run at a time.
 *
 * A second scanner would fight over the same camera, so the module keeps
 * track of the running one and refuses to start another.
 */
let activeScanner = null;

/**
 * The camera scanner for the product search.
 *
 * The scanner is only started when the operator asks for it, never when the
 * page loads. A detected barcode is put into the existing search field and
 * the existing search form is submitted once; the camera is released before
 * that happens, so the light of the camera does not stay on.
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

        init() {
            // Leaving the page releases the camera as well.
            window.addEventListener('pagehide', () => this.stop());
        },

        async open() {
            // The camera is already running for this field: opening it again
            // must not reset the guards that keep a second scan and a second
            // submission from happening.
            if (this.starting || (this.show && this.scanner)) {
                return;
            }

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
        },

        async start() {
            if (! navigator.mediaDevices || ! navigator.mediaDevices.getUserMedia) {
                this.error = 'This browser cannot use the camera. Type the barcode into the search field instead.';

                return;
            }

            if (activeScanner) {
                this.error = 'The camera is already in use. Close the other scanner first.';

                return;
            }

            this.scanner = new Html5Qrcode(readerId, {
                formatsToSupport: SUPPORTED_FORMATS,
                verbose: false,
            });

            activeScanner = this.scanner;

            try {
                await this.scanner.start(
                    // The rear camera of a phone is the one that faces the
                    // barcode.
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 250, height: 160 }, aspectRatio: 1.6 },
                    (decodedText) => this.onScan(decodedText),
                    // A frame without a barcode is not a failure, it is the
                    // normal case while the camera looks at the label.
                    () => {},
                );

                this.status = 'Looking for a barcode…';
            } catch (error) {
                activeScanner = null;
                this.scanner = null;
                this.error = this.describe(error);
            }
        },

        /**
         * A message the operator can act on.
         */
        describe(error) {
            const name = error && error.name ? error.name : '';

            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return 'Camera access was refused. Allow the camera for this site, use a secure connection (HTTPS), or type the barcode into the search field.';
            }

            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'No camera was found on this device. Type the barcode into the search field instead.';
            }

            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'The camera could not be started. Another application may be using it.';
            }

            return 'The camera could not be started. Type the barcode into the search field instead.';
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

            if (! scanner) {
                return;
            }

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
        },

        async close() {
            this.show = false;

            await this.stop();
        },
    };
}
