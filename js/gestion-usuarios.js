document.addEventListener('DOMContentLoaded', () => {
    // --- SEGURIDAD Y SESIÓN ---
    const usuarioData = JSON.parse(sessionStorage.getItem('usuario'));
    if (!usuarioData || usuarioData.Rol !== 2) {
        window.location.href = 'login.html';
        return;
    }
    document.getElementById('user-name').textContent = usuarioData.Nombre;
    document.getElementById('logout-btn').addEventListener('click', () => {
        sessionStorage.removeItem('usuario');
        window.location.href = 'login.html';
    });

    const userForm = document.getElementById('user-form');
    const usersTableBody = document.getElementById('users-table-body');
    const editModal = document.getElementById('edit-modal');
    const editUserForm = document.getElementById('edit-user-form');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');

    // --- FUNCIONES ---
    const fetchUsers = async () => {
        try {
            const response = await fetch('https://grammermx.com/Logistica/RootKanBan/dao/manage_users.php?action=read');
            const result = await response.json();
            if (result.success) {
                renderUsers(result.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Cargar',
                    text: result.message,
                    background: '#1f2937',
                    color: '#ffffff'
                });
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
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.Estatus === '1' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">
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
            const response = await fetch('https://grammermx.com/Logistica/RootKanBan/dao/manage_users.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Usuario creado correctamente.',
                    background: '#1f2937',
                    color: '#ffffff',
                    timer: 2000,
                    showConfirmButton: false
                });
                userForm.reset();
                fetchUsers();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message,
                    background: '#1f2937',
                    color: '#ffffff'
                });
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    usersTableBody.addEventListener('click', (e) => {
        // Botón para cambiar estatus
        if (e.target.classList.contains('toggle-status-btn')) {
            const id = e.target.dataset.id;
            const currentStatus = e.target.dataset.status;
            const newStatus = currentStatus === '1' ? '0' : '1';
            const actionText = newStatus === '1' ? 'activar' : 'inactivar';

            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Quieres ${actionText} a este usuario?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: `Sí, ${actionText}`,
                cancelButtonText: 'No, cancelar',
                background: '#1f2937',
                color: '#ffffff'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('IdUsuarios', id);
                    formData.append('Estatus', newStatus);

                    const response = await fetch('https://grammermx.com/Logistica/RootKanBan/dao/manage_users.php', { method: 'POST', body: formData });
                    const res = await response.json();
                    if (res.success) {
                        fetchUsers();
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: `El usuario ha sido ${actionText}do.`,
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message,
                            background: '#1f2937',
                            color: '#ffffff'
                        });
                    }
                }
            });
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

        const response = await fetch('https://grammermx.com/Logistica/RootKanBan/dao/manage_users.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            editModal.classList.add('hidden');
            fetchUsers();
            Swal.fire({
                icon: 'success',
                title: 'Usuario Actualizado',
                background: '#1f2937',
                color: '#ffffff',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message,
                background: '#1f2937',
                color: '#ffffff'
            });
        }
    });

    // Carga inicial
    fetchUsers();
});

