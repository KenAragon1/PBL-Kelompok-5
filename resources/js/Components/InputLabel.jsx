export default function InputLabel({
    value,
    className = "",
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={`block text-sm text-gray-700 mb-2 ` + className}
        >
            {value ? value : children}
        </label>
    );
}
