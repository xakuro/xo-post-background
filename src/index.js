import { registerPlugin } from '@wordpress/plugins';
import PostBackgroundSidebar from './components/post-background';

registerPlugin( 'xo-post-background', {
	render: PostBackgroundSidebar
} );
