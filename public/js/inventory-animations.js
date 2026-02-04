// Inventory status animations using Lottie
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lottie === 'undefined') {
        console.error('Lottie library not loaded');
        return;
    }

    // Initialize animations if containers exist
    const animationContainers = [
        { id: 'in-stock-animation', url: 'https://assets5.lottiefiles.com/packages/lf20_ysrn2iwp.json' },
        { id: 'low-stock-animation', url: 'https://assets10.lottiefiles.com/packages/lf20_qjosmr4w.json' },
        { id: 'out-of-stock-animation', url: 'https://assets7.lottiefiles.com/packages/lf20_ydo1amjm.json' },
        { id: 'soon-expiring-animation', url: 'https://assets3.lottiefiles.com/packages/lf20_kqfglvmb.json' },
        { id: 'expired-animation', url: 'https://assets2.lottiefiles.com/packages/lf20_yzoqyyqf.json' }
    ];

    animationContainers.forEach(container => {
        const element = document.getElementById(container.id);
        if (element) {
            lottie.loadAnimation({
                container: element,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: container.url
            });
        }
    });
});
