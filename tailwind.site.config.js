export default {
    content: [
        './resources/views/site/**/*.blade.php',
        './resources/views/livewire/site/**/*.blade.php',
        './app/Livewire/Site/**/*.php',
        './public/site/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                brand: '#d97706',
                'brand-dark': '#b45309',
                dark: '#111827',
                muted: '#6b7280',
                soft: '#f9fafb',
            },

            fontFamily: {
                arabic: ['Cairo', 'sans-serif'],
                english: ['Inter', 'sans-serif'],
            },

            boxShadow: {
                soft: '0 18px 50px rgba(15, 23, 42, 0.08)',
            },

            borderRadius: {
                site: '22px',
            },
        },
    },

    plugins: [],
};