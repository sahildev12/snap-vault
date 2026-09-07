# Interactive MAP (CMO Jammu)

Flow:

**Interactive Map → Block page → PHC list → PHC page**

## Data

Block and PHC content lives in [`data/blocks.php`](data/blocks.php).

When the client emails photos and details, update that file:

- `hero` — block/PHC background photo path under `assets/images/map/…`
- `bmo` / `incharge` — name, phone, photo
- `phcs` — list of PHCs under each block
- `gallery`, `infrastructure`, `equipments`, `roadmap`, `staff`

Base map image: `assets/images/map/jammu-blocks.png`

## Marker positions

Each block has `marker: { x, y }` as percentages on the map image. Adjust if a pin looks off.
