module.exports = {
    prefix: 'alt-cookies-',
    content: [
        'resources/views/*.antlers.html',
        'resources/views/*.blade.php',
        'resources/css/*.css',
    ],
    theme: {
        extend: {
            colors:{
                overlay: "rgba(0, 0, 0, 0.6)",
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
    ]
}
