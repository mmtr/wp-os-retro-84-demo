# WordPress OS Retro 84 demo

A live demo of [Retro 84](https://github.com/mmtr/wp-os-retro-84), a desktop theme for [OpenStation](https://github.com/WordPress/openstation), with WordPress running entirely in the browser through [WordPress Playground](https://wordpress.org/playground/).

**Try it: https://wp-os-retro-84.space.fast**

Every visit starts a fresh WordPress in the visitor's own browser. Nothing is stored on a server, and nothing a visitor does reaches anyone else.

## How it works

- `site/index.html` embeds Playground through its JavaScript client and shows a loading window until WordPress is ready. It fetches the two ZIPs from this site and hands them to Playground as files, because Playground runs on its own domain and can only fetch files that allow it.
- `site/openstation.zip` is a trunk build of OpenStation, and `site/theme.zip` is the theme's `main` branch.
- `site/setup.php` runs once inside Playground. It installs the theme, turns OpenStation on with every intro already seen, applies the theme's recommended settings, and adds a few posts, pages and a folder on the desk.

## Updating

Run `./refresh.sh` to fetch the latest OpenStation trunk build and the theme's `main` branch, then publish the `site/` folder to Spacefast as a new version of the same Space. Its ID is in `.spacefast/space.json`.

## License

GPL-2.0-or-later, see `LICENSE.txt`. The fonts in `site/fonts/` are under the SIL Open Font License 1.1.
