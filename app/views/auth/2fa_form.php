
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Code 2FA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen px-4">
    <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-md">
        <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Vérification 2FA</h1>

        <form id="validateForm" action="/verify/2fa-check" method="post" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div>
                <label for="code" class="block font-medium text-gray-700 mb-2">Code reçu :</label>
                <input type="text" id="code" name="code" maxlength="10" placeholder="Ex: 1A8NB5" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="text-center">
                <button id="validateButton" type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 w-full flex justify-center items-center text-lg">
                    <span id="validateText">Valider</span>
                    <svg id="validateSpinner" class="hidden animate-spin ml-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Renvoyer le code -->
        <div class="mt-8 text-center">
            <form id="resendForm" action="/verify/2fa-resend" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button id="resendButton" type="button" class="text-blue-600 text-lg hover:underline disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center">
                    <span id="resendText">Renvoyer le code</span>
                    <svg id="resendSpinner" class="hidden animate-spin ml-2 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>
            </form>
            <p id="timer" class="text-sm text-gray-500 mt-4 hidden"></p>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div x-data="{ show: false, message: '', type: 'success' }" x-show="show" x-transition 
         class="fixed bottom-6 right-6 max-w-xs w-full bg-white shadow-lg rounded-lg p-4 border-l-4"
         :class="type === 'success' ? 'border-green-500' : 'border-red-500'">
        <div class="flex items-start">
            <div class="flex-1">
                <p class="text-gray-800 font-semibold" x-text="message"></p>
            </div>
            <button @click="show = false" class="ml-4 text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
    </div>

    <script>
    // Anti Double Click Valider
    const validateForm = document.getElementById('validateForm');
    const validateButton = document.getElementById('validateButton');
    const validateText = document.getElementById('validateText');
    const validateSpinner = document.getElementById('validateSpinner');

    validateForm.addEventListener('submit', function (e) {
        if (validateButton.disabled) {
            e.preventDefault();
            return false;
        }
        validateButton.disabled = true;
        validateText.textContent = 'Validation...';
        validateSpinner.classList.remove('hidden');
    });

    // Timer System pour Renvoyer (AJAX)
    const resendButton = document.getElementById('resendButton');
    const resendForm = document.getElementById('resendForm');
    const resendText = document.getElementById('resendText');
    const resendSpinner = document.getElementById('resendSpinner');
    const timerDisplay = document.getElementById('timer');

    const delayPhases = [30, 30, 120, 900, 3600, 259200]; // 30s x2, 2min, 15min, 1h, 3j
    let resendCount = 0;

    const initialDelay = 60;
    window.addEventListener('DOMContentLoaded', () => {
        blockResendButton(initialDelay);
    });

    resendButton.addEventListener('click', function () {
        if (resendButton.disabled) return;

        disableResendButton();

        // Fetch POST sans reload
        fetch(resendForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(new FormData(resendForm))
        })
        .then(response => response.text())
        .then(data => {
            console.log("Code renvoyé !");
            showToast('success', 'Un nouveau code a été envoyé à votre adresse e-mail.');
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('error', 'Erreur lors de l’envoi du code.');
        });
    });

    function blockResendButton(delaySeconds) {
        resendButton.disabled = true;
        resendText.textContent = "Envoi...";
        resendSpinner.classList.remove('hidden');
        timerDisplay.classList.remove('hidden');

        // Après 5 secondes, on cache "Envoi..." et le spinner
        setTimeout(() => {
            resendText.textContent = "";
            resendSpinner.classList.add('hidden');
        }, 5000); // 5000ms = 5 secondes

        let remaining = delaySeconds;
        timerDisplay.textContent = `⏳ Veuillez patienter ${formatTime(remaining)} avant de pouvoir renvoyer le code.`;

        let interval = setInterval(() => {
            remaining--;
            timerDisplay.textContent = `⏳ Veuillez patienter ${formatTime(remaining)} avant de pouvoir renvoyer le code.`;

            if (remaining <= 0) {
                clearInterval(interval);
                resendButton.disabled = false;
                resendText.textContent = "Renvoyer le code";
                resendSpinner.classList.add('hidden');
                timerDisplay.classList.add('hidden');
            }
        }, 1000);
    }

    function disableResendButton() {
        let delay = delayPhases[Math.min(resendCount, delayPhases.length - 1)];
        resendCount++;
        blockResendButton(delay);
    }

    function formatTime(totalSeconds) {
        let days = Math.floor(totalSeconds / 86400);
        let hours = Math.floor((totalSeconds % 86400) / 3600);
        let minutes = Math.floor((totalSeconds % 3600) / 60);
        let seconds = totalSeconds % 60;

        function pad(n) {
            return n < 10 ? '0' + n : n;
        }

        if (days > 0) {
            return `${days}j - ${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        } else if (hours > 0) {
            return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        } else {
            return `${pad(minutes)}:${pad(seconds)}`;
        }
    }

    function showToast(type, message) {
        const toast = document.querySelector('[x-data]');
        toast.__x.$data.show = true;
        toast.__x.$data.message = message;
        toast.__x.$data.type = type;

        setTimeout(() => {
            toast.__x.$data.show = false;
        }, 7000);
    }

    // Toasts success / error si existant
    document.addEventListener('DOMContentLoaded', () => {
        <?php if (!empty($_SESSION['success'])): ?>
            setTimeout(() => {
                showToast('success', "<?= htmlspecialchars($_SESSION['success']) ?>");
            }, 500);
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            setTimeout(() => {
                showToast('error', "<?= htmlspecialchars($_SESSION['error']) ?>");
            }, 500);
        <?php unset($_SESSION['error']); endif; ?>
    });
</script>


</body>
</html>
