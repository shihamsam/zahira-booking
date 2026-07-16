/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                pitch: {
                    50: '#EAF3EC',
                    100: '#CFE4D5',
                    400: '#2E9A5C',
                    600: '#1F7A4D',
                    900: '#0B3D2E',
                    950: '#082B20',
                },
                chalk: {
                    50: '#F7F9F5',
                    100: '#EFF2EA',
                    200: '#E7ECE3',
                },
                floodlight: {
                    400: '#F5BE63',
                    500: '#F2A93B',
                    600: '#D98F22',
                },
                clay: {
                    500: '#C1443B',
                    600: '#A6362E',
                },
                ink: {
                    900: '#10231B',
                    700: '#26372F',
                },
            },
            fontFamily: {
                display: ['"Oswald"', 'sans-serif'],
                body: ['"Inter"', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'monospace'],
            },
            borderRadius: {
                card: '0.625rem',
            },
        },
    },
    plugins: [],
};
