document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('mainNavbar');
    const backToTop = document.getElementById('scrollToTop');
    const submenuTrigger = document.getElementById('submenuTrigger');
    const submenuPanel = document.getElementById('submenuPanel');
    const revealElements = document.querySelectorAll('.reveal-title');

    const topBar = document.querySelector('.navbar-top');

    // 1. Efecto Scroll (Shrink Navbar & Reveal BackToTop)
    window.addEventListener('scroll', () => {
        // Navbar
        if (window.scrollY > 50) {
            const topBarHeight = topBar ? topBar.offsetHeight : 0;
            navbar.style.transform = `translateY(-${topBarHeight}px)`;
            backToTop.classList.add('show');
        } else {
            navbar.style.transform = "translateY(0)";
            backToTop.classList.remove('show');
        }

        // 2. Animación de Títulos (Intersection Observer alternativo)
        revealElements.forEach(el => {
            const elementTop = el.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            if (elementTop < windowHeight * 0.85) {
                el.classList.add('visible');
            }
        });
    });

    // 3. Control de Submenú Inteligente
    submenuTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        submenuPanel.classList.toggle('active');
        // Ajustar posición si el navbar ha cambiado de tamaño
        // Calculamos la altura real de las dos barras visibles
        const rect = navbar.getBoundingClientRect();
        submenuPanel.style.top = `${rect.bottom}px`;
    });

    // Cerrar submenú al hacer clic afuera
    document.addEventListener('click', (event) => {
        const isClickInside = submenuPanel.contains(event.target);
        const isTrigger = submenuTrigger.contains(event.target);

        if (!isClickInside && !isTrigger) {
            submenuPanel.classList.remove('active');
        }
    });

    // 4. Scroll Suave hacia arriba
    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // 5. Animación de Iconos en Cards
    const cards = document.querySelectorAll('.card-glass-effect');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            const icon = card.querySelector('i');
            icon.classList.add('fa-bounce');
            setTimeout(() => icon.classList.remove('fa-bounce'), 1000);
        });
    });

    // 6. Efecto Typewriter Reutilizable
    const initTypewriter = (el) => {
        const phrases = el.getAttribute('data-phrases').split('|');
        let phraseIndex = 0;
        let charIndex = 0;

        function type() {
            const currentPhrase = phrases[phraseIndex];

            if (charIndex <= currentPhrase.length) {
                const char = currentPhrase.charAt(charIndex);
                if (char.toLowerCase() === 'x' && el.id === 'typewriter-home') {
                    if (charIndex > 0) {
                        const prevChar = currentPhrase.charAt(charIndex - 1);
                        el.innerHTML = el.innerHTML.slice(0, -1) +
                            `<span style="white-space: nowrap;">${prevChar}<img src="img/icon-wifiexpres.png" class="typewriter-icon" alt="x"></span>`;
                    } else {
                        el.innerHTML += `<img src="img/icon-wifiexpres.png" class="typewriter-icon" alt="x">`;
                    }
                } else {
                    el.insertAdjacentHTML('beforeend', char);
                }
                charIndex++;
                setTimeout(type, 70);
            } else {
                // En lugar de borrar letra a letra, desvanecemos
                setTimeout(() => {
                    el.classList.add('typewriter-fade-out');
                    setTimeout(() => {
                        el.innerHTML = '';
                        el.classList.remove('typewriter-fade-out');
                        charIndex = 0;
                        phraseIndex = (phraseIndex + 1) % phrases.length;
                        type();
                    }, 500); // Duración del desvanecimiento
                }, 2000); // Tiempo que queda la frase visible
            }
        }
        type();
    };
    document.querySelectorAll('.typewriter-target').forEach(el => {
        setTimeout(() => initTypewriter(el), 500);
    });

    // 7. Navegación Ventajas
    const ventajasLinks = document.querySelectorAll('a[href="#ventajas"]');
    ventajasLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            submenuPanel.classList.remove('active');
            const target = document.querySelector('#ventajas');
            if(target) {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Optimización: Pausar animación de órbita si no es visible (opcional para performance)
    const icons = document.querySelectorAll('.orbit-icon');
    window.addEventListener('blur', () => {
        icons.forEach(icon => icon.style.animationPlayState = 'paused');
    });
    window.addEventListener('focus', () => {
        icons.forEach(icon => icon.style.animationPlayState = 'running');
    });
});