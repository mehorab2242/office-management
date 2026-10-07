import { api } from './api';

export interface ChangePasswordPayload {
    current_password: string;
    password: string;
    password_confirmation: string;
}

export async function changePassword(payload: ChangePasswordPayload): Promise<void> {
    await api.post('/me/password', payload);
}
