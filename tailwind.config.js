module.exports = {
    darkMode: "class",
    content: [
        "./vendor/filament/**/*.blade.php",
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./node_modules/flowbite/**/*.js",
        "./resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            backgroundColor: ["peer-checked"],
            textColor: ["peer-checked"],
        },
    },
    plugins: [require("flowbite/plugin")],
};
