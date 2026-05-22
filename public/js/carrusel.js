class InfiniteCarousel {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.inner = this.container.querySelector('.carousel-inner');
        this.items = Array.from(this.inner.querySelectorAll('.carousel-item'));
        this.prevBtn = this.container.querySelector('.prev');
        this.nextBtn = this.container.querySelector('.next');
        
        this.itemCount = this.items.length;
        this.currentIndex = 0;
        this.isDragging = false;
        this.startPos = 0;
        this.currentTranslate = 0;
        this.prevTranslate = 0;
        this.animationID = 0;
        this.isTransitioning = false;
        this.autoplayInterval = null;

        this.init();
    }

    init() {
        // 1. Clonar elementos suficientes para permitir desplazamientos largos
        const firstClones = this.items.slice(0, 10).map(el => el.cloneNode(true));
        const lastClones = this.items.slice(-10).map(el => el.cloneNode(true));

        lastClones.reverse().forEach(clone => this.inner.prepend(clone));
        firstClones.forEach(clone => this.inner.append(clone));

        // 2. Ajustar posición inicial (saltando los 10 clones del principio)
        this.updateVisibleItems();
        this.currentIndex = 10; 
        this.setSliderPositionByIndex(false);

        // 3. Event Listeners de Arrastre
        this.inner.addEventListener('pointerdown', this.dragStart.bind(this));
        this.inner.addEventListener('pointermove', this.dragAction.bind(this));
        this.inner.addEventListener('pointerup', this.dragEnd.bind(this));
        this.inner.addEventListener('pointerleave', this.dragEnd.bind(this));

        // Evitar que las imágenes se arrastren nativamente (causa congelamiento)
        this.inner.querySelectorAll('img').forEach(img => {
            img.ondragstart = () => false;
        });

        // 4. Botones
        if(this.prevBtn) this.prevBtn.addEventListener('click', () => this.move('prev'));
        if(this.nextBtn) this.nextBtn.addEventListener('click', () => this.move('next'));

        // 5. Manejo de transición infinita
        this.inner.addEventListener('transitionend', this.checkIndex.bind(this));

        // Resize
        window.addEventListener('resize', () => {
            this.updateVisibleItems();
            this.setSliderPositionByIndex(false);
        });

        // 6. Configuración de Autoplay
        this.container.addEventListener('mouseenter', () => this.stopAutoplay());
        this.container.addEventListener('mouseleave', () => this.startAutoplay());

        this.startAutoplay();
    }

    updateVisibleItems() {
        const width = window.innerWidth;
        if (width > 1100) this.visibleItems = 5;
        else if (width > 768) this.visibleItems = 3;
        else if (width > 480) this.visibleItems = 2;
        else this.visibleItems = 1;
    }

    dragStart(e) {
        if (this.isTransitioning) return;
        e.preventDefault(); // Evita selección de texto y comportamientos raros
        this.stopAutoplay();
        this.isDragging = true;
        this.startPos = e.pageX;
        this.inner.style.transition = 'none';
        this.container.style.cursor = 'grabbing';
    }

    dragAction(e) {
        if (!this.isDragging) return;
        const currentPosition = e.pageX;
        const diff = currentPosition - this.startPos;
        this.currentTranslate = this.prevTranslate + diff;
        this.setTransform(this.currentTranslate);
    }

    dragEnd() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.container.style.cursor = 'grab';
        
        const itemWidth = this.inner.offsetWidth / this.visibleItems;
        const movedBy = this.currentTranslate - this.prevTranslate;
        
        // Calcula cuántos items se movieron basado en la distancia (redondeado)
        const itemsMoved = Math.round(movedBy / itemWidth);
        this.currentIndex -= itemsMoved;

        this.setSliderPositionByIndex();
        this.startAutoplay();
    }

    move(direction) {
        if (this.isTransitioning) return;
        this.currentIndex = direction === 'next' ? this.currentIndex + 1 : this.currentIndex - 1;
        this.setSliderPositionByIndex();
    }

    setSliderPositionByIndex(withTransition = true) {
        this.isTransitioning = withTransition;
        const itemWidth = this.inner.offsetWidth / this.visibleItems;
        this.currentTranslate = this.currentIndex * -itemWidth;
        this.prevTranslate = this.currentTranslate;
        
        if (withTransition) {
            this.inner.style.transition = 'transform 0.4s ease-out';
        } else {
            this.inner.style.transition = 'none';
        }
        
        this.setTransform(this.currentTranslate);
    }

    setTransform(value) {
        this.inner.style.transform = `translateX(${value}px)`;
    }

    startAutoplay() {
        if (this.autoplayInterval) return;
        this.autoplayInterval = setInterval(() => {
            if (!this.isDragging) this.move('next');
        }, 3000); // Cambia cada 3 segundos
    }

    stopAutoplay() {
        clearInterval(this.autoplayInterval);
        this.autoplayInterval = null;
    }

    checkIndex() {
        this.isTransitioning = false;
        this.inner.style.transition = 'none';
        
        // Reajuste infinito basado en 10 clones
        if (this.currentIndex >= this.itemCount + 10) {
            this.currentIndex = 10;
            this.setSliderPositionByIndex(false);
        }
        if (this.currentIndex <= 4) {
            this.currentIndex = this.itemCount + 4;
            this.setSliderPositionByIndex(false);
        }
        
        this.updateIndicators();
    }

    updateIndicators() {
        const indicators = this.container.querySelectorAll('.indicator');
        if (!indicators.length) return;
        
        indicators.forEach(ind => ind.classList.remove('active'));
        let activeIdx = (this.currentIndex - 10) % this.itemCount;
        if (activeIdx < 0) activeIdx = this.itemCount + activeIdx;
        
        if (indicators[activeIdx]) indicators[activeIdx].classList.add('active');
    }
}

// Inicializar todos los carruseles de la página
document.addEventListener('DOMContentLoaded', () => {
    const carousels = ['jsCarousel1'];
    carousels.forEach(id => new InfiniteCarousel(id));
});