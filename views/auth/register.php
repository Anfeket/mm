<main id="register">
    <h2>Register</h2>
    <form action="/register" method="post">
        <input type="hidden" name="invite" value="<?= htmlspecialchars($_GET['invite'] ?? '') ?>">
        <label>Username: <input type="text" name="username" required></label>
        <label>Email: <input type="email" name="email" required></label>
        <label>Password: <input type="password" name="password" required></label>
        <button type="submit">Register</button>
    </form>
</main>
