/**
 * The grid is rendered on the server (render.php); only its inner blocks
 * (the Pagination block) are serialized into post content.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
