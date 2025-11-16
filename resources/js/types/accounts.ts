export type BankAccount = {
    id: number;
    name: string;
    institution: string;
    balance: number;
    monthlyMovements: {
        income: number;
        expense: number;
    };
};
