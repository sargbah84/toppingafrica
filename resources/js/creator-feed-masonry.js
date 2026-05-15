import Masonry from 'masonry-layout';
import imagesLoaded from 'imagesloaded';

function initCreatorFeedMasonry() {
    const grid = document.querySelector('[data-creator-feed-masonry]');
    if (!grid || grid.dataset.masonryInitialized === '1') {
        return;
    }
    grid.dataset.masonryInitialized = '1';

    const masonry = new Masonry(grid, {
        itemSelector: '[data-masonry-item]',
        columnWidth: '[data-masonry-sizer]',
        gutter: 24,
        percentPosition: true,
        transitionDuration: 0,
    });

    imagesLoaded(grid).on('progress', () => masonry.layout());
}

document.addEventListener('DOMContentLoaded', initCreatorFeedMasonry);
document.addEventListener('livewire:navigated', initCreatorFeedMasonry);
