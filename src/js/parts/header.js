export const stickyHeader = () => {
    const header = document.querySelector('header');

    if (!header) return

    const headerheigth = header.getBoundingClientRect().height;

    const sticky = () => {
        if (window.scrollY > headerheigth) {
            header.classList.add('_sticky')
        }
        else {
            header.classList.remove('_sticky')
        }
    }

    sticky();
    window.addEventListener('scroll', sticky);
}