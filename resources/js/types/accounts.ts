export type BankAccount = {
    id: number;
    name: string;
    institution: string;
    balance: number;
    currency?: string;
    accountType?: string;
    monthlyMovements: {
        income: number;
        expense: number;
    };
};
