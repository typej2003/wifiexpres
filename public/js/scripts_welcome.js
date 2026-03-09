document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const sections = document.querySelectorAll('main section[id]');
    
    // Altura del header fijo (aproximadamente 90px, ajustada en CSS con scroll-padding-top)
    const navbarHeight = 90; 

    // Función para establecer el enlace activo, se utiliza tanto al hacer clic como al desplazarse
    function setActiveLink(targetLink) {
        if (!targetLink) return;

        // 1. Desactiva todos los enlaces
        navLinks.forEach(l => {
            l.classList.remove('active');
        });
        
        // 2. Activa el enlace objetivo
        targetLink.classList.add('active');
        
        // 3. Manejar la activación del padre Dropdown (Ej: Si activamos "Planes Residenciales", activamos "Servicios")
        const parentToggle = targetLink.closest('.dropdown')?.querySelector('.dropdown-toggle');
        if (parentToggle) {
            parentToggle.classList.add('active');
        }
    }

    // Lógica para manejar el Scroll Spy (Detección de sección activa por desplazamiento)
    function handleScrollSpy() {
        let currentSectionId = '';
        
        // Iterar sobre las secciones de atrás hacia adelante para priorizar la más alta
        for (let i = sections.length - 1; i >= 0; i--) {
            const section = sections[i];
            
            // Usamos offsetTop ajustado por la altura de la barra de navegación
            const sectionTop = section.offsetTop - navbarHeight;
            
            // Si el desplazamiento vertical es mayor o igual al inicio de la sección,
            // esa es nuestra sección actual.
            if (window.scrollY >= sectionTop) {
                currentSectionId = section.id;
                break; // Encontramos la sección activa, salimos del bucle
            }
        }

        if (currentSectionId) {
            // Mapeamos el ID de la sección al valor del atributo data-page en el menú de navegación.
            let pageName = '';
            // Mapeo actualizado a los ids actuales del HTML
            if (currentSectionId === 'inicio') pageName = 'inicio';
            else if (currentSectionId === 'nosotros') pageName = 'nosotros';
            else if (currentSectionId === 'servicios') pageName = 'servicios';
            else if (currentSectionId === 'ventajas') pageName = 'ventajas';
            else if (currentSectionId === 'portal-cautivo') pageName = 'portal-cautivo';
            else if (currentSectionId === 'planes') pageName = 'planes';

            
            // Si encontramos un nombre de página válido (y no es una sección auxiliar como 'disfruta')
            if (pageName) {
                // Buscamos el enlace usando el atributo data-page, que es más robusto.
                const targetLink = document.querySelector(`.navbar-nav .nav-link[data-page="${pageName}"]`);
                
                if (targetLink && !targetLink.classList.contains('active')) {
                    setActiveLink(targetLink);
                }
            } 
            // Si la sección actual no tiene un enlace principal (como 'disfruta'),
            // el enlace activo se mantiene en la sección anterior, que es el comportamiento deseado.

        } else {
            // Si estamos en la parte superior de la página (antes de #inicio), activar 'Inicio'
            const inicioLink = document.querySelector('a[data-page="inicio"]');
            if (inicioLink && !inicioLink.classList.contains('active')) {
                setActiveLink(inicioLink);
            }
        }
    }

    // ----------------------------------------------------
    // Event Listeners
    // ----------------------------------------------------

    // 1. Listener para el clic en los enlaces (comportamiento instantáneo)
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const isInternalAnchor = this.getAttribute('href') && this.getAttribute('href').startsWith('#');
            
            // Si es un ancla interna o un elemento de un submenú, lo activamos
            if (isInternalAnchor || this.classList.contains('dropdown-item')) {
                setActiveLink(this);
            }
            
            // Si es un toggle, también lo activamos
            if (this.id && this.id.includes('Dropdown')) {
                setActiveLink(this);
            }
        });
    });

    // Comportamiento especial: cuando se hace clic en el toggle de Servicios
    // queremos: 1) desplazar suavemente a la sección #servicios y 2) abrir el dropdown.
    const serviciosToggle = document.getElementById('serviciosDropdown');
    if (serviciosToggle) {
        serviciosToggle.addEventListener('click', function (e) {
            // Evitamos el comportamiento por defecto del enlace para controlar el flujo
            e.preventDefault();

            const target = document.getElementById('servicios');
            const dropdown = bootstrap.Dropdown.getOrCreateInstance(serviciosToggle);

            if (target) {
                // Calculamos la posición objetivo teniendo en cuenta la altura del navbar
                const targetTop = Math.max(0, target.offsetTop - navbarHeight);

                // Iniciamos un scroll suave hacia la sección
                window.scrollTo({ top: targetTop, behavior: 'smooth' });

                // Abrir el dropdown cuando el scroll llegue cerca de la posición objetivo.
                // Usamos un listener temporal de scroll y un fallback por timeout.
                let handled = false;

                function onScrollCheck() {
                    const closeEnough = Math.abs(window.scrollY - targetTop) <= 6;
                    const atBottom = (window.innerHeight + window.scrollY) >= (document.body.scrollHeight - 2);
                    if (closeEnough || atBottom) {
                        if (!handled) {
                            dropdown.show();
                            setActiveLink(serviciosToggle);
                            handled = true;
                            window.removeEventListener('scroll', onScrollCheck);
                        }
                    }
                }

                window.addEventListener('scroll', onScrollCheck);

                // Fallback: si por alguna razón no se detecta el final del scroll, abrir después de 800ms
                setTimeout(() => {
                    if (!handled) {
                        dropdown.show();
                        setActiveLink(serviciosToggle);
                        window.removeEventListener('scroll', onScrollCheck);
                        handled = true;
                    }
                }, 800);

            } else {
                // Si no existe la sección (caso raro), simplemente togglear el dropdown
                dropdown.toggle();
                setActiveLink(serviciosToggle);
            }
        });
    }
    
    // 2. Listener para el desplazamiento (comportamiento Scroll Spy)
    window.addEventListener('scroll', handleScrollSpy);

    // ----------------------------------------------------
    // Inicialización al cargar la página
    // ----------------------------------------------------
    
    // Llamar a Scroll Spy una vez al inicio para establecer la sección activa (por si se recarga en medio de la página)
    handleScrollSpy();
});