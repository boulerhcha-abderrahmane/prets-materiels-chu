// Intersection Observer for animations
const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.1
};

const animateElement = (entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            observer.unobserve(entry.target); // Stop observing once animated
        }
    });
};

const observer = new IntersectionObserver(animateElement, observerOptions);

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.contact-item');
    elements.forEach((element, index) => {
        // Set initial state
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
        element.style.transitionDelay = `${index * 200}ms`;
        observer.observe(element);
    });
});

// Back to top button functionality with throttling
const backToTopButton = document.querySelector('.back-to-top');
let isThrottled = false;
const throttleTime = 100; // ms

const handleScroll = () => {
    if (!isThrottled) {
        isThrottled = true;
        
        requestAnimationFrame(() => {
            const scrolled = window.scrollY;
            const trigger = 300;
            
            if (scrolled > trigger) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
            
            setTimeout(() => {
                isThrottled = false;
            }, throttleTime);
        });
    }
};

const scrollToTop = (e) => {
    e.preventDefault();
    
    const duration = 800;
    const start = window.scrollY;
    const startTime = performance.now();
    
    const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
    
    const animateScroll = currentTime => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        window.scrollTo(0, start * (1 - easeOutCubic(progress)));
        
        if (progress < 1) {
            requestAnimationFrame(animateScroll);
        }
    };
    
    requestAnimationFrame(animateScroll);
};

// Event listeners with passive option for better performance
window.addEventListener('scroll', handleScroll, { passive: true });
backToTopButton.addEventListener('click', scrollToTop);

// Cleanup function
const cleanup = () => {
    window.removeEventListener('scroll', handleScroll);
    backToTopButton.removeEventListener('click', scrollToTop);
    observer.disconnect();
};

// Add cleanup on page navigation using pagehide instead of unload
window.addEventListener('pagehide', cleanup); 