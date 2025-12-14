    </main>
    <footer>
        <p>© 2025 Mi Tienda Online</p>
    </footer>
    <script>
        const ALL_LANG_DATA = <?= json_encode($allLangData) ?>;
        let currentLang = "<?= Translator::getCurrentLanguage() ?>";

        document.addEventListener('DOMContentLoaded', function() {
            const langSelect = document.getElementById('lang_select');
            const selectedFlag = document.getElementById('selected_flag');
            const availableLangs = <?= json_encode($availableLanguages) ?>;

            function updateFlag(selectedLangCode) {
                const lang = availableLangs.find(l => l.idi_nombre === selectedLangCode);
                if (lang && lang.idi_bandera_url) {
                    selectedFlag.src = lang.idi_bandera_url;
                    selectedFlag.style.display = 'inline-block';
                } else {
                    selectedFlag.style.display = 'none';
                }
            }

            function applyTranslation() {
                document.querySelectorAll('[data-translate-key]:not(input)').forEach(element => {
                    const key = element.getAttribute('data-translate-key');
                    if (ALL_LANG_DATA[currentLang] && ALL_LANG_DATA[currentLang][key]) {
                        element.textContent = ALL_LANG_DATA[currentLang][key];
                    } else {
                        // Fallback to key if translation not found
                        element.textContent = key;
                    }
                });
                // Handle input placeholders or other attributes if necessary
            }

            // Initial display
            updateFlag(currentLang);
            applyTranslation();

            window.updateLanguage = function(selectedLangCode) {
                // Update client-side language immediately
                currentLang = selectedLangCode;
                updateFlag(selectedLangCode);
                applyTranslation();           

                const userId = <?= $_SESSION['user_id'] ?? 0 ?>;
                const formData = new FormData();
                formData.append('lang', selectedLangCode);
                if (userId) {
                    formData.append('user_id', userId);
                }

                fetch('set_language.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Language preference updated successfully on server.');
                    } else {
                        console.error('Failed to update language preference on server:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error sending language update to server:', error);
                });
            };
        });
    </script>
</body>
</html>
