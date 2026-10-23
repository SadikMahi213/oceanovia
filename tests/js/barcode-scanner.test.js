import test from 'node:test';
import assert from 'node:assert/strict';

import {
    barcodeScanner,
    cameraAccessProblem,
    cameraDiagnostics,
    cameraErrorMessage,
    cameraErrorName,
    cameraMessages,
} from '../../resources/js/barcode-scanner.js';

/**
 * A window that looks like a secure one with a working camera API.
 */
function secureWindow(overrides = {}) {
    return {
        isSecureContext: true,
        navigator: {
            mediaDevices: {
                getUserMedia: () => {
                    throw new Error('the camera must not be requested in this test');
                },
            },
        },
        addEventListener: () => {},
        ...overrides,
    };
}

/**
 * A window of a page that is served over plain HTTP.
 */
function insecureWindow() {
    return {
        isSecureContext: false,
        navigator: {},
        addEventListener: () => {},
    };
}

test('an insecure page is reported as insecure, not as an unsupported browser', () => {
    assert.equal(cameraAccessProblem(insecureWindow()), 'insecure');
    assert.equal(
        cameraAccessProblem({ isSecureContext: false, navigator: { mediaDevices: { getUserMedia() {} } } }),
        'insecure',
    );
});

test('a secure page without the camera API is an unsupported browser', () => {
    assert.equal(cameraAccessProblem(secureWindow({ navigator: {} })), 'unsupported');
    assert.equal(
        cameraAccessProblem(secureWindow({ navigator: { mediaDevices: {} } })),
        'unsupported',
    );
});

test('a secure page with the camera API has no problem', () => {
    assert.equal(cameraAccessProblem(secureWindow()), null);
});

test('every camera failure gets its own message', () => {
    assert.equal(cameraErrorMessage({ name: 'NotAllowedError' }), cameraMessages.permission);
    assert.equal(cameraErrorMessage({ name: 'PermissionDeniedError' }), cameraMessages.permission);
    assert.equal(cameraErrorMessage({ name: 'SecurityError' }), cameraMessages.permission);
    assert.equal(cameraErrorMessage({ name: 'NotFoundError' }), cameraMessages.noCamera);
    assert.equal(cameraErrorMessage({ name: 'DevicesNotFoundError' }), cameraMessages.noCamera);
    assert.equal(cameraErrorMessage({ name: 'NotReadableError' }), cameraMessages.inUse);
    assert.equal(cameraErrorMessage({ name: 'TrackStartError' }), cameraMessages.inUse);
    assert.equal(cameraErrorMessage({ name: 'AbortError' }), cameraMessages.inUse);
    assert.match(cameraErrorMessage({ message: 'the camera is gone' }), /the camera is gone/);
    assert.equal(cameraErrorMessage({}), cameraMessages.failed);
});

test('the errors the scanner library reports as plain strings are understood', () => {
    // The library rejects with strings, not with error objects.
    assert.equal(
        cameraErrorName('Error getting userMedia, error = NotAllowedError: Permission denied'),
        'NotAllowedError',
    );
    assert.equal(
        cameraErrorMessage('Error getting userMedia, error = NotAllowedError: Permission denied'),
        cameraMessages.permission,
    );
    assert.equal(
        cameraErrorName('Error getting userMedia, error = NotFoundError: Requested device not found'),
        'NotFoundError',
    );
    assert.equal(
        cameraErrorMessage('Error getting userMedia, error = NotFoundError: Requested device not found'),
        cameraMessages.noCamera,
    );
    assert.equal(
        cameraErrorName('Error getting userMedia, error = NotReadableError: Could not start source'),
        'NotReadableError',
    );
    assert.equal(
        cameraErrorMessage('Error getting userMedia, error = NotReadableError: Could not start source'),
        cameraMessages.inUse,
    );
    assert.equal(
        cameraErrorName('Error getting userMedia, error = OverconstrainedError: Cannot satisfy both'),
        'OverconstrainedError',
    );
    assert.equal(
        cameraErrorName('Camera access is only supported in secure context like https or localhost.'),
        'InsecureContextError',
    );
    assert.equal(
        cameraErrorMessage('Camera access is only supported in secure context like https or localhost.'),
        cameraMessages.insecure,
    );
    assert.equal(
        cameraErrorName("The device doesn't support navigator.mediaDevices , only supported cameraIdOrConfig in this case is deviceId parameter (string)."),
        'NotSupportedError',
    );
    assert.equal(
        cameraErrorMessage("The device doesn't support navigator.mediaDevices , only supported cameraIdOrConfig in this case is deviceId parameter (string)."),
        cameraMessages.unsupported,
    );
    assert.equal(cameraErrorName('HTML Element with id=reader not found'), '');
    assert.match(
        cameraErrorMessage('HTML Element with id=reader not found'),
        /HTML Element with id=reader not found/,
    );
});

test('the messages all keep the manual way of entering a barcode', () => {
    for (const message of Object.values(cameraMessages)) {
        assert.match(message, /search field|try again|instead|it/i);
    }
});

/**
 * The scanner with a search field, a form and a camera reader.
 */
function scannerWithDom({ submissions = [] } = {}) {
    const input = {
        value: '',
        events: [],
        dispatchEvent(event) {
            this.events.push(event.type);
            return true;
        },
        closest() {
            return {
                requestSubmit() {
                    submissions.push('submitted');
                },
            };
        },
    };

    const tracks = [{ stopped: false, stop() { this.stopped = true; } }];

    const video = { srcObject: { getTracks: () => tracks } };
    const reader = { querySelector: (selector) => (selector === 'video' ? video : null) };

    globalThis.document = {
        querySelector: (selector) => (selector === '#search' ? input : null),
        getElementById: (id) => (id === 'reader' ? reader : null),
    };

    const scanner = barcodeScanner('#search', 'reader');
    scanner.$nextTick = () => Promise.resolve();

    return { scanner, input, tracks, submissions };
}

test('an insecure page never asks for the camera and says why', async () => {
    const { scanner, submissions } = scannerWithDom();
    globalThis.window = insecureWindow();

    await scanner.open();

    assert.equal(scanner.error, cameraMessages.insecure);
    assert.notEqual(scanner.error, cameraMessages.unsupported);
    assert.equal(scanner.scanner, null);
    assert.deepEqual(submissions, []);
});

test('a scan fills the search field and submits the search exactly once', async () => {
    const { scanner, input, submissions } = scannerWithDom();
    globalThis.window = secureWindow();

    scanner.onScan(' 4006381333931 ');

    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(input.value, '4006381333931');
    assert.deepEqual(input.events, ['input']);
    assert.deepEqual(submissions, ['submitted']);
});

test('the decoder reporting the same code again does not submit again', async () => {
    const { scanner, submissions } = scannerWithDom();
    globalThis.window = secureWindow();

    scanner.onScan('4006381333931');
    scanner.onScan('4006381333931');
    scanner.onScan('4006381333931');

    await new Promise((resolve) => setImmediate(resolve));

    assert.deepEqual(submissions, ['submitted']);
});

test('closing the scanner stops every camera track', async () => {
    const { scanner, tracks } = scannerWithDom();
    globalThis.window = secureWindow();

    await scanner.close();

    assert.equal(tracks[0].stopped, true);
});

test('the view going away stops the camera as well', async () => {
    const { scanner, tracks } = scannerWithDom();
    globalThis.window = secureWindow();

    scanner.destroy();

    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(tracks[0].stopped, true);
});

/**
 * Captures console output from cameraDiagnostics so assertions can inspect it.
 */
function captureDiagnostics(context, error) {
    const logs = [];
    const originalGroupCollapsed = console.groupCollapsed;
    const originalLog = console.log;
    console.groupCollapsed = (...args) => logs.push({ type: 'group', args });
    console.log = (...args) => logs.push({ type: 'log', args });
    try {
        cameraDiagnostics(error, context);
    } finally {
        console.groupCollapsed = originalGroupCollapsed;
        console.log = originalLog;
    }
    return logs;
}

test('cameraDiagnostics captures secure context and camera API availability', () => {
    const logs = captureDiagnostics(secureWindow(), { name: 'NotAllowedError', message: 'Permission denied' });

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /secureContext.*true/);
    assert.match(logText, /mediaDevices.*true/);
    assert.match(logText, /getUserMedia.*true/);
    assert.match(logText, /errorName.*NotAllowedError/);
    assert.match(logText, /errorMessage.*Permission denied/);
});

test('cameraDiagnostics reports missing getUserMedia', () => {
    const logs = captureDiagnostics(
        { isSecureContext: true, navigator: { mediaDevices: {} }, self: window, top: window },
        { name: 'NotSupportedError' },
    );

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /mediaDevices.*true/);
    assert.match(logText, /getUserMedia.*false/);
    assert.match(logText, /errorName.*NotSupportedError/);
});

test('cameraDiagnostics reports insecure context by not logging', () => {
    const logs = captureDiagnostics(
        { isSecureContext: false, navigator: {}, self: window, top: window },
        { name: 'NotAllowedError' },
    );

    assert.equal(logs.length, 0, 'cameraDiagnostics should not log on insecure context');
});

test('cameraDiagnostics reports iframe context', () => {
    const logs = captureDiagnostics(
        { isSecureContext: true, navigator: { mediaDevices: { getUserMedia() {} } }, self: {}, top: {} },
        { name: 'NotAllowedError' },
    );

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /inIframe.*true/);
});

test('cameraDiagnostics reports NotAllowedError after user grants permission', () => {
    // Simulates the real-world scenario: user clicked "Allow" in Chrome but
    // getUserMedia still rejects with NotAllowedError. The diagnostics capture
    // the actual error name so the root cause can be identified (e.g.
    // Permissions-Policy header, iframe without allow=camera, or Chrome not
    // applying the permission until page reload).
    const logs = captureDiagnostics(secureWindow(), { name: 'NotAllowedError' });

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /errorName.*NotAllowedError/);
    assert.match(logText, /inIframe/);
    assert.match(logText, /secureContext.*true/);
});

test('cameraDiagnostics captures OverconstrainedError for bad camera constraints', () => {
    const logs = captureDiagnostics(secureWindow(), {
        name: 'OverconstrainedError',
        message: 'Cannot satisfy facingMode environment constraint',
    });

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /errorName.*OverconstrainedError/);
    assert.match(logText, /Cannot satisfy facingMode environment constraint/);
});

test('cameraDiagnostics captures NotReadableError for camera in use', () => {
    const logs = captureDiagnostics(secureWindow(), {
        name: 'NotReadableError',
        message: 'Could not start video source',
    });

    const logText = logs.map(l => JSON.stringify(l.args)).join(' ');
    assert.match(logText, /errorName.*NotReadableError/);
    assert.match(logText, /Could not start video source/);
});
