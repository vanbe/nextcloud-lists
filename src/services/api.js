import axios from '@nextcloud/axios'

const BASE = '/ocs/v2.php/apps/lists/api/v1'

const ocsClient = axios.create({
	headers: { 'OCS-APIRequest': 'true' },
})

function unwrap(response) {
	return response.data.ocs.data
}

export const listsApi = {
	getAll: () => ocsClient.get(`${BASE}/lists?format=json`).then(unwrap),
	create: (name, description = null, icon = null) =>
		ocsClient.post(`${BASE}/lists?format=json`, { name, description, icon }).then(unwrap),
	update: (id, fields) =>
		ocsClient.put(`${BASE}/lists/${id}?format=json`, fields).then(unwrap),
	destroy: (id) => ocsClient.delete(`${BASE}/lists/${id}?format=json`),
}
