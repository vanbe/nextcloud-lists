import axios from '@nextcloud/axios'

const BASE = '/ocs/v2.php/apps/lists/api/v1'
const OCS_HEADERS = { 'OCS-APIRequest': 'true' }

function unwrap(response) {
	return response.data.ocs.data
}

export const usersApi = {
	searchUsers: (q) =>
		axios.get(`${BASE}/users/search?format=json&q=${encodeURIComponent(q)}`, { headers: OCS_HEADERS }).then(unwrap),
	searchGroups: (q) =>
		axios.get(`${BASE}/groups/search?format=json&q=${encodeURIComponent(q)}`, { headers: OCS_HEADERS }).then(unwrap),
}

export const sharesApi = {
	getAll: (listId) =>
		axios.get(`${BASE}/lists/${listId}/shares?format=json`, { headers: OCS_HEADERS }).then(unwrap),
	create: (listId, shareType, shareWith, permissions = 1) =>
		axios.post(`${BASE}/lists/${listId}/shares?format=json`, { shareType, shareWith, permissions }, { headers: OCS_HEADERS }).then(unwrap),
	update: (listId, id, permissions) =>
		axios.put(`${BASE}/lists/${listId}/shares/${id}?format=json`, { permissions }, { headers: OCS_HEADERS }).then(unwrap),
	destroy: (listId, id) =>
		axios.delete(`${BASE}/lists/${listId}/shares/${id}?format=json`, { headers: OCS_HEADERS }),
}

export const itemsApi = {
	suggest: (listId, q) =>
		axios.get(`${BASE}/lists/${listId}/items/suggest?format=json&q=${encodeURIComponent(q)}`, { headers: OCS_HEADERS }).then(unwrap),
	getAll: (listId) =>
		axios.get(`${BASE}/lists/${listId}/items?format=json`, { headers: OCS_HEADERS }).then(unwrap),
	create: (listId, title, categoryId = null) =>
		axios.post(`${BASE}/lists/${listId}/items?format=json`, { title, categoryId }, { headers: OCS_HEADERS }).then(unwrap),
	update: (listId, id, fields) =>
		axios.put(`${BASE}/lists/${listId}/items/${id}?format=json`, fields, { headers: OCS_HEADERS }).then(unwrap),
	toggle: (listId, id) =>
		axios.post(`${BASE}/lists/${listId}/items/${id}/toggle?format=json`, {}, { headers: OCS_HEADERS }).then(unwrap),
	destroy: (listId, id) =>
		axios.delete(`${BASE}/lists/${listId}/items/${id}?format=json`, { headers: OCS_HEADERS }),
}

export const categoriesApi = {
	getAll: (listId) =>
		axios.get(`${BASE}/lists/${listId}/categories?format=json`, { headers: OCS_HEADERS }).then(unwrap),
	create: (listId, name) =>
		axios.post(`${BASE}/lists/${listId}/categories?format=json`, { name }, { headers: OCS_HEADERS }).then(unwrap),
	update: (listId, id, name) =>
		axios.put(`${BASE}/lists/${listId}/categories/${id}?format=json`, { name }, { headers: OCS_HEADERS }).then(unwrap),
	destroy: (listId, id) =>
		axios.delete(`${BASE}/lists/${listId}/categories/${id}?format=json`, { headers: OCS_HEADERS }),
}

export const listsApi = {
	getAll: () =>
		axios.get(`${BASE}/lists?format=json`, { headers: OCS_HEADERS }).then(unwrap),
	create: (name, description = null, hasQuantities = false) =>
		axios.post(`${BASE}/lists?format=json`, { name, description, hasQuantities: hasQuantities ? 1 : 0 }, { headers: OCS_HEADERS }).then(unwrap),
	update: (id, fields) =>
		axios.put(`${BASE}/lists/${id}?format=json`, fields, { headers: OCS_HEADERS }).then(unwrap),
	destroy: (id) =>
		axios.delete(`${BASE}/lists/${id}?format=json`, { headers: OCS_HEADERS }),
}
