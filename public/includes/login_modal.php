<div class="modal-overlay" id="loginModal">
    <div class="modal-box">
        <h2>Staff/Admin Login</h2>
        <p class="sub">Sign in to manage announcements and document requests.</p>

        <?php if (!empty($loginError)): ?>
            <div class="form-error"><?= e($loginError) ?></div>
        <?php endif; ?>

        <form method="post" action="login_process.php" autocomplete="off" id="loginForm">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="admin" value="" required autocomplete="off">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" placeholder="••••••••" value="" required autocomplete="off">
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password" tabindex="-1">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" id="loginCancel" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Log in</button>
            </div>
        </form>
        <p class="demo-hint">Demo login: admin / admin123</p>
    </div>
</div>
<?php if (!empty($loginError)): ?>
<script>document.getElementById('loginModal').classList.add('open');</script>
<?php endif; ?>

