import axios from 'axios';

const client = axios.create();

function request(method, url, data, expectedStatusCodes, expectedCallback, errorCallback) {
	const isDelete = method === 'delete';
	const config = {
		validateStatus: statusCode => expectedStatusCodes.includes(statusCode)
	};

	if (isDelete && data) {
		config.data = data;
	}

	const args = isDelete ? [url, config] : (data ? [url, data, config] : [url, config]);

	client[method](...args)
		.then(res => expectedCallback(res))
		.catch(err => errorCallback(err));
}

export function http_get(url, expectedStatusCodes, expectedCallback, errorCallback) {
	request('get', url, null, expectedStatusCodes, expectedCallback, errorCallback);
}

export function http_post(url, body, expectedStatusCodes, expectedCallback, errorCallback) {
	request('post', url, body, expectedStatusCodes, expectedCallback, errorCallback);
}

export function http_put(url, body, expectedStatusCodes, expectedCallback, errorCallback) {
	request('put', url, body, expectedStatusCodes, expectedCallback, errorCallback);
}

export function http_patch(url, body, expectedStatusCodes, expectedCallback, errorCallback) {
	request('patch', url, body, expectedStatusCodes, expectedCallback, errorCallback);
}

export function http_delete(url, body, expectedStatusCodes, expectedCallback, errorCallback) {
	request('delete', url, body, expectedStatusCodes, expectedCallback, errorCallback);
}