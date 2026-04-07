import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Create a global store for sidebar state
Alpine.store('sidebar', {
    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    
    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebarCollapsed', this.collapsed);
        window.dispatchEvent(new Event('sidebarToggle'));
    },
    
    init() {
        // Listen for storage changes
        window.addEventListener('storage', (e) => {
            if (e.key === 'sidebarCollapsed') {
                this.collapsed = e.newValue === 'true';
            }
        });
        
        // Listen for custom toggle events
        window.addEventListener('sidebarToggle', () => {
            this.collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        });
    }
});

Alpine.start();
