document.addEventListener('DOMContentLoaded', () => {
    // --- SEGURIDAD Y SESIÓN ---

    /*
    const usuarioData = JSON.parse(sessionStorage.getItem('usuario'));
    if (!usuarioData || usuarioData.Rol !== '2') {
        window.location.href = 'login.html';
        return;
    }
    document.getElementById('user-name').textContent = usuarioData.Nombre;
    document.getElementById('logout-btn').addEventListener('click', () => {
        sessionStorage.removeItem('usuario');
        window.location.href = 'login.html';
    });*/

    // --- ELEMENTOS DEL DOM ---
    const userForm = document.getElementById('user-form');
    const usersTableBody = document.getElementById('users-table-body');
    const editModal = document.getElementById('edit-modal');
    const editUserForm = document.getElementById('edit-user-form');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');

    // --- FUNCIONES ---
    const fetchUsers = async () => {
        try {
            const response = await fetch('php/manage_users.php?action=read');
            const result = await response.json();
            if (result.success) {
                renderUsers(result.data);
            } else {
                alert('Error al cargar usuarios: ' + result.message);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
        }
    };

    const renderUsers = (users) => {
        usersTableBody.innerHTML = '';
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.className = 'bg-gray-800 border-b border-gray-700';
            tr.innerHTML = `
                <td class="px-4 py-3 font-medium">${user.IdUsuarios}</td>
                <td class="px-4 py-3">${user.Nombre}</td>
                <td class="px-4 py-3">${user.Rol === '1' ? 'Conductor' : 'Administrador'}</td>
                <td class="px-4 py-3">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.Estatus === '1' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${user.Estatus === '1' ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td class="px-4 py-3 text-center space-x-2">
                    <button class="toggle-status-btn text-sm ${user.Estatus === '1' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700'} text-white px-2 py-1 rounded" data-id="${user.IdUsuarios}" data-status="${user.Estatus}">
                        ${user.Estatus === '1' ? 'Inactivar' : 'Activar'}
                    </button>
                    <button class="edit-btn text-sm bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-user='${JSON.stringify(user)}'>Editar</button>
                </td>
            `;
            usersTableBody.appendChild(tr);
        });
    };

    // --- EVENT LISTENERS ---
    userForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(userForm);
        formData.append('action', 'create');

        try {
            const response = await fetch('php/manage_users.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert('Usuario creado con éxito.');
                userForm.reset();
                fetchUsers();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    usersTableBody.addEventListener('click', async (e) => {
        // Botón para cambiar estatus
        if (e.target.classList.contains('toggle-status-btn')) {
            const id = e.target.dataset.id;
            const currentStatus = e.target.dataset.status;
            const newStatus = currentStatus === '1' ? '0' : '1';

            if (confirm(`¿Seguro que quieres ${newStatus === '1' ? 'activar' : 'inactivar'} a este usuario?`)) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('IdUsuarios', id);
                formData.append('Estatus', newStatus);

                const response = await fetch('php/manage_users.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) fetchUsers(); else alert(result.message);
            }
        }
        // Botón para editar
        if (e.target.classList.contains('edit-btn')) {
            const userData = JSON.parse(e.target.dataset.user);
            document.getElementById('edit-IdUsuarios').value = userData.IdUsuarios;
            document.getElementById('edit-Correo').value = userData.Correo;
            document.getElementById('edit-Rol').value = userData.Rol;
            document.getElementById('edit-Password').value = '';
            editModal.classList.remove('hidden');
        }
    });

    cancelEditBtn.addEventListener('click', () => editModal.classList.add('hidden'));

    editUserForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editUserForm);
        formData.append('action', 'update');

        const response = await fetch('php/manage_users.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            alert('Usuario actualizado.');
            editModal.classList.add('hidden');
            fetchUsers();
        } else {
            alert('Error: ' + result.message);
        }
    });

    // Carga inicial
    fetchUsers();
});
