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
                blue: {
                    500: '#020c2e',
                }
            }
        },
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
    ]
}
