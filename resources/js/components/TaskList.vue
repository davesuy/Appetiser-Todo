<template>
  <div class="container mx-auto p-4">
    <div v-if="isAuthenticated">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold">Tasks for {{ name }}</h1>
        <button @click="logout" class="bg-red-500 text-white px-4 py-2 rounded">Logout</button>
      </div>
      <form @submit.prevent="createTask" class="mb-4" enctype="multipart/form-data">
          <h1 class="text-2xl font-bold">New Task</h1>
        <input v-model="newTask.title" type="text" placeholder="Task Title" required class="border p-2 mb-2 w-full" />
        <textarea v-model="newTask.description" placeholder="Task Description" required class="border p-2 mb-2 w-full"></textarea>
        <input v-model="newTask.due_date" type="date" placeholder="Due Date" class="border p-2 mb-2 w-full" />
        <select v-model="newTask.priority" class="border p-2 mb-2 w-full">
          <option value="" disabled selected>Select Priority</option>
          <option value="Urgent">Urgent</option>
          <option value="High">High</option>
          <option value="Normal">Normal</option>
          <option value="Low">Low</option>
        </select>
        <input type="file" @change="handleFileUpload" multiple class="border p-2 mb-2 w-full" />
        <select v-model="newTask.tags" multiple class="border p-2 mb-2 w-full">
          <option v-for="tag in availableTags" :key="tag.id" :value="tag.name">{{ tag.name }}</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Add Task</button>
      </form>
      <form v-if="editingTask" @submit.prevent="updateTask" class="mb-4 edit-task-form" enctype="multipart/form-data">
          <h1 class="text-2xl font-bold">Edit Task</h1>
        <input v-model="editingTask.title" type="text" placeholder="Task Title" required class="border p-2 mb-2 w-full" />
        <textarea v-model="editingTask.description" placeholder="Task Description" required class="border p-2 mb-2 w-full"></textarea>
        <input v-model="editingTask.due_date" type="date" placeholder="Due Date" class="border p-2 mb-2 w-full" />
        <select v-model="editingTask.priority" class="border p-2 mb-2 w-full">
          <option value="" disabled selected>Select Priority</option>
          <option value="Urgent">Urgent</option>
          <option value="High">High</option>
          <option value="Normal">Normal</option>
          <option value="Low">Low</option>
        </select>
        <input type="file" @change="handleFileUpload" multiple class="border p-2 mb-2 w-full" />
        <select v-model="editingTask.tags" multiple class="border p-2 mb-2 w-full">
          <option v-for="tag in availableTags" :key="tag.id" :value="tag.name">{{ tag.name }}</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Task</button>
        <button @click="cancelEdit" class="bg-gray-500 text-white px-4 py-2 rounded ml-2">Cancel</button>
      </form>
      <div class="mb-4">
        <label for="sortField" class="mr-2">Sort by:</label>
        <select v-model="sortField" id="sortField" class="border p-2 mb-2 mr-3">
          <option value="created_at">Created At</option>
          <option value="completed_at">Completed</option>
          <option value="priority">Priority</option>
          <option value="due_date">Due Date</option>
        </select>
        <select v-model="sortOrder" class="border p-2 mb-2">
          <option value="asc">Ascending</option>
          <option value="desc">Descending</option>
        </select>
      </div>
      <div class="mb-4">
        <label for="filterCompleted" class="mr-2">Filter by:</label>
        <select v-model="filterCompleted" id="filterCompleted" class="border p-2 mb-2 mr-3">
          <option value="">All</option>
          <option value="completed">Completed</option>
          <option value="todo">Todo</option>
        </select>
        <select v-model="filterPriority" class="border p-2 mb-2 mr-3">
          <option value="">All Priorities</option>
          <option value="Urgent">Urgent</option>
          <option value="High">High</option>
          <option value="Normal">Normal</option>
          <option value="Low">Low</option>
        </select>

<!--        <input v-model="filterDueFrom" type="date" placeholder="Due From" class="border p-2 mb-2" />-->
          <label for="filterCompleted" class="mr-2"> Due:</label>
        <input v-model="filterDueTo" type="date" placeholder="Due To" class="border p-2 mb-2 mr-3" />
        <select v-model="filterArchived" class="border p-2 mb-2 mr-3">
          <option value="">All</option>
          <option value="archived">Archived</option>
          <option value="active">Active</option>
        </select>
        <input v-model="filterSearch" type="text" placeholder="Search by Title" class="border p-2 mb-2" />
      </div>
      <ul class="space-y-2">
        <li v-for="task in tasks" :key="task.id" :class="{'archived-task': task.archived_at}" class="border p-4 rounded flex justify-between items-center">
          <div>
            <span v-if="task.completed_at" class="line-through text-gray-500">
              {{ task.title }} (Completed)
            </span>
            <span v-else>
              {{ task.title }}
            </span>
            <div v-if="task.due_date" class="text-sm text-gray-500">
              Due: {{ task.due_date }}
            </div>
            <div v-if="task.priority" class="text-sm text-gray-500">
              Priority: {{ task.priority }}
            </div>
            <div v-if="task.tags.length" class="text-sm text-gray-500">
              Tags: <span v-for="(tag, index) in task.tags" :key="tag.id">{{ tag.name }}<span v-if="index < task.tags.length - 1">, </span></span>
            </div>
          </div>
          <div class="space-x-2">
            <button @click="markAsCompleted(task.id)" v-if="!task.completed_at" class="bg-green-500 text-white px-4 py-2 rounded">Complete</button>
            <button @click="markAsIncomplete(task.id)" v-if="task.completed_at" class="bg-yellow-500 text-white px-4 py-2 rounded">Incomplete</button>
            <button @click="archiveTask(task.id)" v-if="!task.archived_at" class="bg-yellow-500 text-white px-4 py-2 rounded">Archive</button>
            <button @click="restoreTask(task.id)" v-if="task.archived_at" class="bg-green-500 text-white px-4 py-2 rounded">Restore</button>
            <button @click="editTask(task)" class="bg-blue-500 text-white px-4 py-2 rounded">Edit</button>
            <button @click="deleteTask(task.id)" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
          </div>
        </li>
      </ul>
     <div class="mt-4 flex justify-center space-x-2">
      <button
        @click="fetchTasks(pagination.prev_page_url)"
        :disabled="!pagination.prev_page_url"
        :class="{'bg-gray-500': !pagination.prev_page_url, 'bg-blue-500': pagination.prev_page_url}"
        class="text-white px-4 py-2 rounded"
      >
        Previous
      </button>
      <button
        @click="fetchTasks(pagination.next_page_url)"
        :disabled="!pagination.next_page_url"
        :class="{'bg-gray-500': !pagination.next_page_url, 'bg-blue-500': pagination.next_page_url}"
        class="text-white px-4 py-2 rounded"
      >
        Next
      </button>
    </div>
    </div>
    <div v-else>
      <div class="text-center mt-4">
        <h1 class="text-2xl font-bold mb-4">To start managing your tasks, please log in or register:</h1>
        <div class="flex justify-center space-x-4">
          <router-link to="/login" class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-blue-600 transition duration-300">Login</router-link>
          <router-link to="/register" class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-green-600 transition duration-300">Register</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const tasks = ref([]);
const isAuthenticated = ref(false);
const name = ref('');
const newTask = ref({ title: '', description: '', due_date: '', priority: '', tags: [] });
const editingTask = ref(null);
const sortField = ref('created_at');
const sortOrder = ref('desc');
const filterCompleted = ref('');
const filterPriority = ref('');
const filterDueFrom = ref('');
const filterDueTo = ref('');
const filterArchived = ref('');
const filterSearch = ref('');
const router = useRouter();
const files = ref([]);
const availableTags = ref([]);
const authToken = ref('');
const pagination = ref({});

const checkAuth = () => {
  const token = localStorage.getItem('auth_token');
  isAuthenticated.value = !!token;
  authToken.value = token;
};

const fetchUser = async () => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    const response = await axios.get('/api/user', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    name.value = response.data.name || 'Unknown';
  }
};

const fetchTasks = async (url = '/api/tasks') => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    const response = await axios.get(url, {
      headers: {
        'Authorization': `Bearer ${token}`
      },
      params: {
        sort_field: sortField.value,
        sort_order: sortOrder.value,
        completed: filterCompleted.value,
        priority: filterPriority.value,
        due_from: filterDueFrom.value,
        due_to: filterDueTo.value,
        archived: filterArchived.value,
        search: filterSearch.value
      }
    });
    tasks.value = response.data.data;
    pagination.value = {
      prev_page_url: response.data.prev_page_url,
      next_page_url: response.data.next_page_url,
    };
  }
};

const fetchTags = async () => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    const response = await axios.get('/api/tags', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    availableTags.value = response.data;
  }
};

const handleFileUpload = (event) => {
  files.value = event.target.files;
};

const createTask = async () => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    const formData = new FormData();
    formData.append('title', newTask.value.title);
    formData.append('description', newTask.value.description);
    formData.append('due_date', newTask.value.due_date);
    formData.append('priority', newTask.value.priority);
    formData.append('tags', newTask.value.tags);
    for (let i = 0; i < files.value.length; i++) {
      formData.append('attachments[]', files.value[i]);
    }
    await axios.post('/api/tasks', formData, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    newTask.value = { title: '', description: '', due_date: '', priority: '', tags: [] };
    files.value = [];
    fetchTasks();
  }
};

const editTask = (task) => {
  editingTask.value = { ...task, tags: task.tags.map(tag => tag.name) };
};

const updateTask = async () => {
  const token = localStorage.getItem('auth_token');
  if (token && editingTask.value) {
    const formData = new FormData();
    formData.append('title', editingTask.value.title);
    formData.append('description', editingTask.value.description);
    formData.append('due_date', editingTask.value.due_date);
    formData.append('priority', editingTask.value.priority);
    formData.append('tags', editingTask.value.tags);
    for (let i = 0; i < files.value.length; i++) {
      formData.append('attachments[]', files.value[i]);
    }
    await axios.put(`/api/tasks/${editingTask.value.id}`, formData, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    editingTask.value = null;
    files.value = [];
    fetchTasks();
  }
};

const cancelEdit = () => {
  editingTask.value = null;
};

const deleteTask = async (id) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.delete(`/api/tasks/${id}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    fetchTasks();
  }
};

const archiveTask = async (id) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.patch(`/api/tasks/${id}/archive`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    fetchTasks();
  }
};

const restoreTask = async (id) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.patch(`/api/tasks/${id}/restore`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    fetchTasks();
  }
};

const markAsCompleted = async (id) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.patch(`/api/tasks/${id}/complete`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    fetchTasks();
  }
};

const markAsIncomplete = async (id) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.patch(`/api/tasks/${id}/incomplete`, {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    fetchTasks();
  }
};

const logout = async () => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    await axios.post('/api/logout', {}, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    localStorage.removeItem('auth_token');
    isAuthenticated.value = false;
    router.push('/login');
  }
};

onMounted(() => {
  checkAuth();
  if (isAuthenticated.value) {
    fetchUser();
    fetchTasks();
    fetchTags();
  }
});

watch([sortField, sortOrder, filterCompleted, filterPriority, filterDueFrom, filterDueTo, filterArchived, filterSearch], () => {
    fetchTasks();
});
</script>

<style scoped>
.archived-task {
    background-color: #f0f0f0;
    color: #a0a0a0;
    text-decoration: line-through;
}

.edit-task-form {
    background-color: darkgray;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
