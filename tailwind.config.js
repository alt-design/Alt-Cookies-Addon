module.exports = {
    prefix: 'alt-cookie-',
    content: [
        'resources/views/*.antlers.html',
        'resources/views/*.blade.php',
        'resources/css/*.css',
    ],
    theme: {
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
    ]
}
