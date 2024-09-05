import preset from "./vendor/filament/support/tailwind.config.preset";

module.exports = {
    darkMode: "class",
    presets: [preset],
    content: [
        "./vendor/filament/**/*.blade.php",
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            backgroundColor: ["peer-checked"],
            textColor: ["peer-checked"],
        },
    },
    plugins: [],
};
