export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
    /** When present, the toast gets an Undo button that sends this request. */
    undo?: {
        url: string;
        method: 'get' | 'post' | 'patch' | 'put' | 'delete';
        data?: Record<string, string | number>;
    };
};
