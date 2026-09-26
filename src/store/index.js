/**
 * The `tfpg` Interactivity API store, shared by all three blocks.
 *
 * All three block.json files point their `viewScriptModule` at this single
 * file, so the browser loads and evaluates it once (ES modules are cached by
 * URL) no matter which of the blocks are on the page. Because every action of
 * every block lives here, a block that only appears after a client-side
 * navigation (e.g. the Pagination block once filters are cleared) is always
 * interactive, even on WordPress versions whose router does not load new
 * script modules on navigation.
 *
 * The Posts Filter and the Posts Grid never talk to each other directly: they
 * share this store's `state` and the URL, which is why they can be placed
 * anywhere on the page, in any number, without being nested.
 */
import './core';
import './filter';
import './pagination';
