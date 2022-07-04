export declare class QuillHtmlLogger {
    private debug;
    setDebug(debug: boolean): void;
    prefixString(): string;
    get log(): (...args: any) => void;
}
