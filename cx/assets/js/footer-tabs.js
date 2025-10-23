// JavaScript para el nuevo Footer con Pestañas
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad mejorada de las pestañas
    const tabItems = document.querySelectorAll('.tab-item');
    
    tabItems.forEach(tab => {
        // Efecto de hover mejorado
        tab.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(-1px)';
            }
        });
        
        tab.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateY(0)';
            }
        });
        
        // Efecto de click con feedback visual
        tab.addEventListener('click', function(e) {
            // Agregar efecto de ripple
            const ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.6)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = '50%';
            ripple.style.top = '50%';
            ripple.style.width = '20px';
            ripple.style.height = '20px';
            ripple.style.marginLeft = '-10px';
            ripple.style.marginTop = '-10px';
            
            this.style.position = 'relative';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Animación de entrada del footer
    const footer = document.querySelector('.modern-footer');
    if (footer) {
        footer.style.opacity = '0';
        footer.style.transform = 'translateY(100%)';
        
        setTimeout(() => {
            footer.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            footer.style.opacity = '1';
            footer.style.transform = 'translateY(0)';
        }, 100);
    }
});

// CSS para la animación ripple
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
