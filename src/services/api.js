import axios from '@nextcloud/axios'

const BASE = '/ocs/v2.php/apps/lists/api/v1'
const OCS_HEADERS = { 'OCS-APIRequest': 'true' }

function unwrap(response) {
	return response.data.ocs.data
}

export const listsApi = {
	getAll: () =>
		axios.get(`${BASE}/lists?format=json`, { headers: OCS_HEADERS }).then(unwrap),
	create: (name, description = null, icon = null) =>
		axios.post(`${BASE}/lists?format=json`, { name, description, icon }, { headers: OCS_HEADERS }).then(unwrap),
	update: (id, fields) =>
		axios.put(`${BASE}/lists/${id}?format=json`, fields, { headers: OCS_HEADERS }).then(unwrap),
	destroy: (id) =>
		axios.delete(`${BASE}/lists/${id}?format=json`, { headers: OCS_HEADERS }),
}
