<template>
    <!--
        Mobile  : min-h-screen, natural scroll, columns stack vertically.
        Desktop : h-screen, overflow clipped, columns sit side-by-side.
    -->
    <div class="min-h-screen flex flex-col lg:h-screen lg:overflow-hidden lg:flex-row">
        <slot />
    </div>
</template>
