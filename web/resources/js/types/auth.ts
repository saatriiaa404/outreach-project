export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: 'PETUGAS' | 'KEPALA' | 'ADMIN';
    status: 'ACTIVE' | 'INACTIVE';
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
