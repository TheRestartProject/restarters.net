export declare function OutputHTMLParser(inputHtmlFromQuillPopup: string): string;
export declare function ConvertMultipleSpacesToSingle(input: string): string;
export declare function PreserveNewlinesBr(input: string): string;
export declare function PreserveNewlinesPTags(input: string): string;
export declare function FixTagSpaceOpenTag(input: string): string;
export declare function FixTagSpaceCloseTag(input: string): string;
export declare function Compose<T>(functions: Array<(input: T) => T>, input: T): T;
