<p class="breadcrumb">
    <a href="/">Start</a> / Admin / User
</p>

<h1>User verwalten</h1>

<div x-data="userAdmin()">
    <!-- Neuen User anlegen -->
    <details>
        <summary>Neuen User anlegen</summary>
        <form @submit.prevent="createUser" class="user-form">
            <div class="grid">
                <label>
                    E-Mail
                    <input type="email" x-model="newUser.email" required>
                </label>
                <label>
                    Passwort
                    <input type="password" x-model="newUser.password" required minlength="8">
                </label>
            </div>
            <label>
                Rolle
                <select x-model="newUser.role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button type="submit">User anlegen</button>
        </form>
    </details>

    <p x-show="message" :class="messageType" x-text="message"></p>

    <!-- User-Liste -->
    <?php if (empty($users)): ?>
        <p>Noch keine User vorhanden.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr data-id="<?= htmlspecialchars($user['id']) ?>">
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $user['active'] ? 'active' : 'inactive' ?>">
                                <?= $user['active'] ? 'Aktiv' : 'Gesperrt' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <button class="outline small"
                                    @click="toggleUser('<?= htmlspecialchars($user['id']) ?>', <?= $user['active'] ? 'false' : 'true' ?>)">
                                <?= $user['active'] ? 'Sperren' : 'Aktivieren' ?>
                            </button>
                            <button class="outline secondary small"
                                    @click="deleteUser('<?= htmlspecialchars($user['id']) ?>', '<?= htmlspecialchars($user['email']) ?>')">
                                Löschen
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
