// Configuração da API
const API_URL = 'http://localhost:8080/api';
let currentUser = null;
let projectsCache = []; // cache de projetos para preencher selects/filtrar

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    setupEventListeners();
});

// Event Listeners
function setupEventListeners() {
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    document.getElementById('register-form').addEventListener('submit', handleRegister);
    document.getElementById('task-form').addEventListener('submit', handleCreateTask);
    document.getElementById('project-form').addEventListener('submit', handleCreateProject);
}

// ==================== AUTENTICAÇÃO ====================

async function checkAuth() {
    try {
        const response = await fetch(`${API_URL}/auth/me`, {
            credentials: 'include'
        });

        if (response.ok) {
            const data = await response.json();
            currentUser = data.data.user;
            showMainScreen();
        } else {
            showAuthScreen();
        }
    } catch (error) {
        showAuthScreen();
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        const response = await fetch(`${API_URL}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            currentUser = result.data.user;
            showMainScreen();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao fazer login: ' + error.message);
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        const response = await fetch(`${API_URL}/auth/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            currentUser = result.data.user;
            showMainScreen();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Erro ao registrar: ' + error.message);
    }
}

async function logout() {
    try {
        await fetch(`${API_URL}/auth/logout`, {
            method: 'POST',
            credentials: 'include'
        });
        currentUser = null;
        showAuthScreen();
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
    }
}

// ==================== INTERFACE ====================

function showAuthScreen() {
    document.getElementById('auth-screen').classList.remove('hidden');
    document.getElementById('main-screen').classList.add('hidden');
}

function showMainScreen() {
    document.getElementById('auth-screen').classList.add('hidden');
    document.getElementById('main-screen').classList.remove('hidden');
    document.getElementById('user-name').textContent = currentUser.name;
    loadDashboard();
    loadTasks();
    loadProjects();
}

function showTab(tab) {
    const loginTab = document.getElementById('tab-login');
    const registerTab = document.getElementById('tab-register');
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (tab === 'login') {
        loginTab.classList.add('border-primary', 'text-primary');
        loginTab.classList.remove('text-gray-500');
        registerTab.classList.remove('border-primary', 'text-primary');
        registerTab.classList.add('text-gray-500');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.classList.add('border-primary', 'text-primary');
        registerTab.classList.remove('text-gray-500');
        loginTab.classList.remove('border-primary', 'text-primary');
        loginTab.classList.add('text-gray-500');
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
    hideError();
}

function showSection(section) {
    const tasksSection = document.getElementById('tasks-section');
    const projectsSection = document.getElementById('projects-section');
    const tasksBtn = document.getElementById('section-tasks');
    const projectsBtn = document.getElementById('section-projects');

    if (section === 'tasks') {
        tasksSection.classList.remove('hidden');
        projectsSection.classList.add('hidden');
        tasksBtn.classList.add('border-primary', 'text-primary');
        tasksBtn.classList.remove('text-gray-500');
        projectsBtn.classList.remove('border-primary', 'text-primary');
        projectsBtn.classList.add('text-gray-500');
        loadTasks();
    } else {
        projectsSection.classList.remove('hidden');
        tasksSection.classList.add('hidden');
        projectsBtn.classList.add('border-primary', 'text-primary');
        projectsBtn.classList.remove('text-gray-500');
        tasksBtn.classList.remove('border-primary', 'text-primary');
        tasksBtn.classList.add('text-gray-500');
        loadProjects();
    }
}

function showError(message) {
    const errorDiv = document.getElementById('auth-error');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
}

function hideError() {
    document.getElementById('auth-error').classList.add('hidden');
}

// ==================== DASHBOARD ====================

async function loadDashboard() {
    try {
        const response = await fetch(`${API_URL}/dashboard/stats`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success) {
            const stats = result.data;
            renderDashboardStats(stats);
        }
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
    }
}

function renderDashboardStats(stats) {
    const container = document.getElementById('dashboard-stats');
    container.innerHTML = `
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total de Tarefas</p>
                    <p class="text-3xl font-bold text-gray-900">${stats.tasks.total}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-tasks text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pendentes</p>
                    <p class="text-3xl font-bold text-yellow-600">${stats.tasks.pending}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Concluídas</p>
                    <p class="text-3xl font-bold text-green-600">${stats.tasks.completed}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Atrasadas</p>
                    <p class="text-3xl font-bold text-red-600">${stats.tasks.overdue}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>
    `;
}

// ==================== PROJETOS (cache/populate) ====================

async function loadProjects() {
    try {
        const response = await fetch(`${API_URL}/projects`, { credentials: 'include' });
        const result = await response.json();

        if (result.success) {
            projectsCache = result.data.projects || [];
            renderProjects(projectsCache);
            populateProjectFilters();
            populateTaskProjectSelect();
        }
    } catch (error) {
        console.error('Erro ao carregar projetos:', error);
    }
}

function populateProjectFilters() {
    const filter = document.getElementById('filter-project');
    if (!filter) return;

    // Remove opções exceto as primeiras duas
    while (filter.options.length > 2) filter.remove(2);

    projectsCache.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.text = p.title;
        filter.appendChild(opt);
    });
}

function populateTaskProjectSelect() {
    const sel = document.getElementById('task-project-select');
    if (!sel) return;

    // limpa exceto a primeira
    while (sel.options.length > 1) sel.remove(1);

    projectsCache.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.text = p.title;
        sel.appendChild(opt);
    });
}

// ==================== TAREFAS ====================

async function loadTasks() {
    const status = document.getElementById('filter-status').value;
    const priority = document.getElementById('filter-priority').value;
    const project = document.getElementById('filter-project') ? document.getElementById('filter-project').value : '';

    let url = `${API_URL}/tasks?`;
    if (status) url += `status=${encodeURIComponent(status)}&`;
    if (priority) url += `priority=${encodeURIComponent(priority)}&`;
    if (project) url += `project_id=${encodeURIComponent(project)}&`;

    try {
        const response = await fetch(url, { credentials: 'include' });
        const result = await response.json();

        if (result.success) {
            renderTasks(result.data.tasks);
        }
    } catch (error) {
        console.error('Erro ao carregar tarefas:', error);
    }
}

function renderTasks(tasks) {
    const container = document.getElementById('tasks-list');

    if (!tasks || tasks.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8">Nenhuma tarefa encontrada</p>';
        return;
    }

    container.innerHTML = tasks.map(task => `
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">${task.title}</h3>
                        ${getPriorityBadge(task.priority)}
                        ${getStatusBadge(task.status)}
                    </div>
                    ${task.description ? `<p class="text-gray-600 mb-3">${task.description}</p>` : ''}
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span><i class="far fa-calendar mr-1"></i> ${formatDate(task.due_date)}</span>
                        ${task.attachment_path ? `<a href="${task.attachment_path}" target="_blank" class="text-primary underline">Anexo</a>` : ''}
                    </div>
                </div>
                <div class="flex space-x-2">
                    ${task.status !== 'completed' ? `
                        <button onclick="completeTask(${task.id})" class="text-green-600 hover:text-green-700" title="Marcar como concluída">
                            <i class="fas fa-check-circle text-xl"></i>
                        </button>
                    ` : `
                        <button onclick="toggleComplete(${task.id}, false)" class="text-yellow-600 hover:text-yellow-700" title="Marcar como não concluída">
                            <i class="fas fa-undo text-xl"></i>
                        </button>
                    `}
                    <button onclick="editTask(${task.id})" class="text-blue-600 hover:text-blue-700" title="Editar">
                        <i class="fas fa-edit text-xl"></i>
                    </button>
                    <button onclick="deleteTask(${task.id})" class="text-red-600 hover:text-red-700" title="Excluir">
                        <i class="fas fa-trash text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

async function handleCreateTask(e) {
    e.preventDefault();
    const formEl = e.target;
    const formData = new FormData(formEl);
    const taskId = formData.get('task_id');

    const data = {
        title: formData.get('title'),
        description: formData.get('description'),
        due_date: formData.get('due_date'),
        priority: formData.get('priority'),
        status: 'pending',
        project_id: formData.get('project_id') || null
    };

    try {
        let response, result;

        if (taskId) {
            response = await fetch(`${API_URL}/tasks/${taskId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            result = await response.json();
        } else {
            response = await fetch(`${API_URL}/tasks`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(data)
            });
            result = await response.json();
        }

        if (result.success) {
            const createdTask = result.data.task;
            // Se houver anexo, enviar após criar/atualizar
            const fileInput = document.getElementById('task-attachment');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const targetTaskId = createdTask ? createdTask.id : taskId;
                if (targetTaskId) {
                    await uploadTaskAttachment(targetTaskId, file);
                }
            }

            closeTaskModal();
            loadTasks();
            loadDashboard();
            loadProjects();
        } else {
            alert(result.message || 'Erro ao salvar tarefa');
        }
    } catch (error) {
        alert('Erro ao criar/atualizar tarefa: ' + error.message);
    }
}

async function uploadTaskAttachment(taskId, file) {
    try {
        const fd = new FormData();
        fd.append('task_id', taskId);
        fd.append('file', file);

        const resp = await fetch(`${API_URL}/upload/task`, {
            method: 'POST',
            credentials: 'include',
            body: fd
        });

        if (!resp.ok) {
            console.warn('Falha no upload do anexo');
        }
    } catch (error) {
        console.error('Erro ao enviar anexo:', error);
    }
}

async function completeTask(id) {
    if (!confirm('Marcar esta tarefa como concluída?')) return;

    try {
        const response = await fetch(`${API_URL}/tasks/${id}/complete`, {
            method: 'PATCH',
            credentials: 'include'
        });

        if (response.ok) {
            loadTasks();
            loadDashboard();
        }
    } catch (error) {
        alert('Erro ao completar tarefa: ' + error.message);
    }
}

async function toggleComplete(id, makeComplete = true) {
    // Se já estiver completed e o backend não tem endpoint para "uncomplete" via PATCH,
    // podemos chamar PUT para atualizar status 'pending' (o backend aceita update)
    try {
        const status = makeComplete ? 'completed' : 'pending';
        const resp = await fetch(`${API_URL}/tasks/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ status })
        });
        if (resp.ok) {
            loadTasks();
            loadDashboard();
        }
    } catch (error) {
        console.error('Erro ao alternar conclusão:', error);
    }
}

async function deleteTask(id) {
    if (!confirm('Tem certeza que deseja excluir esta tarefa?')) return;

    try {
        const response = await fetch(`${API_URL}/tasks/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        if (response.ok) {
            loadTasks();
            loadDashboard();
            loadProjects();
        }
    } catch (error) {
        alert('Erro ao excluir tarefa: ' + error.message);
    }
}

function clearFilters() {
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-priority').value = '';
    if (document.getElementById('filter-project')) document.getElementById('filter-project').value = '';
    loadTasks();
}

// ==================== PROJETOS (render) ====================

function renderProjects(projects) {
    const container = document.getElementById('projects-list');

    if (!projects || projects.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500 py-8 col-span-full">Nenhum projeto encontrado</p>';
        return;
    }

    container.innerHTML = projects.map(project => {
        // Verifica se é owner (user_role vem da query ou comparando user_id)
        const isOwner = project.user_role === 'owner' || project.user_id === currentUser.id;

        return `
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-900">${project.title}</h3>
                ${isOwner ? '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Owner</span>' : '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Membro</span>'}
            </div>
            ${project.description ? `<p class="text-gray-600 mb-4">${project.description}</p>` : ''}
            <div class="space-y-2 text-sm text-gray-500">
                <div><i class="far fa-calendar mr-2"></i>${formatDate(project.start_date)} - ${formatDate(project.end_date)}</div>
            </div>
            <div class="mt-4 pt-4 border-t flex justify-between items-center">
                <button onclick="viewProjectDetails(${project.id})" class="text-primary hover:text-blue-700">
                    <i class="fas fa-eye mr-1"></i>Ver Detalhes
                </button>
                ${isOwner ? `
                <div class="flex space-x-2">
                    <button onclick="showAddMemberModal(${project.id})" class="text-green-600 hover:text-green-700" title="Adicionar membro">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button onclick="deleteProject(${project.id})" class="text-red-600 hover:text-red-700" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                ` : ''}
            </div>
        </div>
    `}).join('');
}

async function viewProjectDetails(projectId) {
    try {
        const response = await fetch(`${API_URL}/projects/${projectId}`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success) {
            const project = result.data.project;
            showProjectDetailsModal(project);
        }
    } catch (error) {
        alert('Erro ao carregar detalhes: ' + error.message);
    }
}

function showProjectDetailsModal(project) {
    const isOwner = project.user_id === currentUser.id;

    const membersHtml = project.members.map(m => `
        <div class="flex items-center justify-between p-2 border-b">
            <div>
                <span class="font-medium">${m.name}</span>
                <span class="text-sm text-gray-500 ml-2">${m.email}</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="px-2 py-1 ${m.role === 'owner' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'} text-xs rounded-full">
                    ${m.role === 'owner' ? 'Owner' : 'Membro'}
                </span>
                ${isOwner && m.user_id !== project.user_id ? `<button onclick="removeMember(${project.id}, ${m.user_id})" class="text-red-600 hover:text-red-700" title="Remover membro"><i class="fas fa-user-minus"></i></button>` : ''}
            </div>
        </div>
    `).join('');

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold">${project.title}</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            ${project.description ? `<p class="text-gray-600 mb-4">${project.description}</p>` : ''}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Início</label>
                    <p class="text-gray-900">${formatDate(project.start_date)}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Término</label>
                    <p class="text-gray-900">${formatDate(project.end_date)}</p>
                </div>
            </div>
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Membros (${project.members_count})</h4>
                <div class="border rounded-lg">${membersHtml}</div>
            </div>
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Tarefas</h4>
                <div id="project-tasks-list-${project.id}" class="space-y-2"></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Depois de mostrar modal, buscar e renderizar tarefas do projeto
    loadProjectTasks(project.id);
}

async function loadProjectTasks(projectId) {
    try {
        const resp = await fetch(`${API_URL}/projects/${projectId}/tasks`, { credentials: 'include' });
        const res = await resp.json();
        if (res.success) {
            const container = document.getElementById(`project-tasks-list-${projectId}`);
            if (!container) return;

            const tasks = res.data.tasks || res.data || [];

            container.innerHTML = tasks.map(t => `
                <div class="p-2 border rounded flex justify-between items-center">
                    <div>
                        <div class="font-medium">${t.title}</div>
                        <div class="text-sm text-gray-500">${formatDate(t.due_date)} ${t.priority ? ' • ' + t.priority : ''}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        ${t.attachment_path ? `<a href="${t.attachment_path}" target="_blank" class="text-primary underline">Anexo</a>` : ''}
                        <button onclick="editTask(${t.id})" class="text-blue-600 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteTask(${t.id})" class="text-red-600 hover:text-red-700"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Erro ao carregar tarefas do projeto:', error);
    }
}

async function removeMember(projectId, userId) {
    if (!confirm('Remover este membro do projeto?')) return;

    try {
        const resp = await fetch(`${API_URL}/projects/${projectId}/members/${userId}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        if (resp.ok) {
            alert('Membro removido com sucesso');
            // fechar modal atual e reabrir para forçar atualizar
            document.querySelectorAll('.fixed.inset-0').forEach(n => n.remove());
            viewProjectDetails(projectId);
            loadProjects();
        } else {
            const r = await resp.json();
            alert(r.message || 'Erro ao remover membro');
        }
    } catch (error) {
        console.error('Erro ao remover membro:', error);
    }
}

async function showAddMemberModal(projectId) {
    try {
        // Busca usuários disponíveis
        const response = await fetch(`${API_URL}/users`, { credentials: 'include' });
        const result = await response.json();

        if (result.success) {
            const usersHtml = result.data.users.map(u => `
                <option value="${u.id}">${u.name} (${u.email})</option>
            `).join('');

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-md w-full p-6">
                    <h3 class="text-xl font-bold mb-4">Adicionar Membro</h3>
                    <form onsubmit="handleAddMember(event, ${projectId})" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Selecione o usuário</label>
                            <select name="user_id" required class="w-full px-4 py-2 border rounded-lg">
                                <option value="">Escolha um usuário...</option>
                                ${usersHtml}
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg hover:bg-blue-600">
                                Adicionar
                            </button>
                            <button type="button" onclick="this.closest('.fixed').remove()" class="flex-1 border py-2 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);
        }
    } catch (error) {
        alert('Erro ao buscar usuários: ' + error.message);
    }
}

async function handleAddMember(e, projectId) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        user_id: parseInt(formData.get('user_id'))
    };

    try {
        const response = await fetch(`${API_URL}/projects/${projectId}/members`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            e.target.closest('.fixed').remove();
            alert('Membro adicionado com sucesso!');
            loadProjects();
        } else {
            alert(result.message || 'Erro ao adicionar membro');
        }
    } catch (error) {
        alert('Erro ao adicionar membro: ' + error.message);
    }
}

// ==================== CRIAR/ATUALIZAR PROJETO ====================

async function handleCreateProject(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        title: formData.get('title'),
        description: formData.get('description'),
        start_date: formData.get('start_date'),
        end_date: formData.get('end_date')
    };

    try {
        const response = await fetch(`${API_URL}/projects`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            closeProjectModal();
            loadProjects();
        } else {
            alert(result.message || 'Erro ao criar projeto');
        }
    } catch (error) {
        alert('Erro ao criar projeto: ' + error.message);
    }
}

async function deleteProject(id) {
    if (!confirm('Tem certeza que deseja excluir este projeto?')) return;

    try {
        const response = await fetch(`${API_URL}/projects/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        });

        if (response.ok) {
            loadProjects();
        }
    } catch (error) {
        alert('Erro ao excluir projeto: ' + error.message);
    }
}

// ==================== EDITAR TAREFA ====================

async function editTask(id) {
    try {
        const resp = await fetch(`${API_URL}/tasks/${id}`, { credentials: 'include' });
        const r = await resp.json();
        if (r.success && r.data && r.data.task) {
            const task = r.data.task;
            // preenche o modal
            document.getElementById('task-id').value = task.id;
            document.getElementById('task-title').value = task.title || '';
            document.getElementById('task-description').value = task.description || '';
            document.getElementById('task-due-date').value = task.due_date || '';
            document.getElementById('task-priority').value = task.priority || 'medium';
            if (task.project_id) document.getElementById('task-project-select').value = task.project_id;
            // mostra modal
            showTaskModal();
        } else {
            alert('Tarefa não encontrada');
        }
    } catch (error) {
        console.error('Erro ao buscar tarefa:', error);
    }
}

// ==================== MODALS ====================

function showTaskModal() {
    // limpar campos para novo item
    document.getElementById('task-id').value = '';
    document.getElementById('task-form').reset();
    // preencher select de projetos
    populateTaskProjectSelect();
    document.getElementById('task-modal').classList.remove('hidden');
}

function closeTaskModal() {
    document.getElementById('task-modal').classList.add('hidden');
    document.getElementById('task-form').reset();
    document.getElementById('task-id').value = '';
}

function showProjectModal() {
    document.getElementById('project-modal').classList.remove('hidden');
}

function closeProjectModal() {
    document.getElementById('project-modal').classList.add('hidden');
    document.getElementById('project-form').reset();
}

// ==================== HELPERS ====================

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function getPriorityBadge(priority) {
    const badges = {
        high: '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Alta</span>',
        medium: '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Média</span>',
        low: '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Baixa</span>'
    };
    return badges[priority] || '';
}

function getStatusBadge(status) {
    const badges = {
        pending: '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Pendente</span>',
        in_progress: '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Em Progresso</span>',
        completed: '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Concluída</span>'
    };
    return badges[status] || '';
}
