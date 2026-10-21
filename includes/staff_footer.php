            </main>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 class="modal-title">Delete Account</h2>
            </div>
            <div class="modal-body">
                <div class="form-warning">
                    <strong>Warning:</strong> This action cannot be undone. Your account and associated data will be permanently deleted.
                </div>

                <?php if (isset($delete_error)): ?>
                    <div class="alert-banner" style="margin-bottom: var(--space-5);">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <div class="alert-content">
                            <div class="alert-message"><?= htmlspecialchars($delete_error) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-input"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button type="submit" form="deleteAccountForm" class="btn btn-accent">
                    Delete Account
                </button>
            </div>
        </div>
    </div>

    <script>
        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('active');
            document.getElementById('confirm_password').value = '';
            document.getElementById('confirm_password').focus();
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('confirm_password').value = '';
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });

        <?php if (isset($delete_error)): ?>
        window.addEventListener('load', function() {
            openDeleteModal();
        });
        <?php endif; ?>
    </script>

    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
