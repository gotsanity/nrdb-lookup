NRDB-Lookup is a plugin for wordpress that is in the very early stages of development. The plugin is used to query netrunnerdb.com for card art and decklists. At the moment mouseovers and embeding of images is supported as well as automatic daily updating of the cardlist.

##INSALL:

Download and place in plugins directory. Ensure assets/ is writable. Enable plugin in dashboard.


##How it works (shortcode examples):
* To place a mouseover of a card: [nrdb]Off the Grid[/nrdb]
* To embed an image (centered): [nrdb embed="center"]Off the Grid[/nrdb]
* To embed an image (centered, with larger image): [nrdb embed="center" size="large"]Off the Grid[/nrdb]
* To float the embed left or right: [nrdb embed="left"]Off the Grid[/nrdb]
* To embed a decklist (in development): [nrdb decklist="decklist-ID#"]Name of Deck[/nrdb]


##TODO:
* Add support for decklists.
* Add local caching of images.
* Settings page
* Manual asset update button
* Additional paramaters for embedding/mouseovers
* Better handling of card names and fuzzy logic
* Worpress WYSIWYG editing buttons.
