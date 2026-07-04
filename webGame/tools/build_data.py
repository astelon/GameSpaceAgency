#!/usr/bin/env python3
"""Build webGame data files from the master cards.csv.

Generates:
  webGame/data/cards.json      - card database for the frontend
  webGame/api/engine/cards_data.php - same database for the PHP engine
  copies card art + icons into webGame/assets/

Run from repo root:  python3 webGame/tools/build_data.py
"""
import csv, json, os, shutil, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CSV = os.path.join(ROOT, 'cards', 'cards.csv')
WEB = os.path.join(ROOT, 'webGame')
ART_SRC = os.path.join(ROOT, 'cards', 'art', 'generated_backup')
ICON_SRC = os.path.join(ROOT, 'cards', 'art', 'icons')


def to_int(v):
    v = (v or '').strip()
    if v in ('', '-'):
        return None
    try:
        return int(v)
    except ValueError:
        return None


def main():
    rows = list(csv.DictReader(open(CSV, encoding='utf-8-sig')))
    cards = {}
    for r in rows:
        cid = r['CardID'].strip()
        tags = [t.strip() for t in (r['Tags'] or '').split(';') if t.strip()]
        card = {
            'id': cid,
            'type': r['Type'].strip(),            # Engine/Tank/Payload/Support/Tech/Mission/Event
            'name': r['Name'].strip(),
            'cost': to_int(r['Cost']) or 0,
            'thrust': to_int(r['Thrust']),
            'range': to_int(r['Range']),
            'mass': to_int(r['Mass']),
            'energy': to_int(r['Energy']),        # negative = usage cost hint, positive = generation
            'energyMode': (r['EnergyMode'] or '').strip(),  # Gen / Use / Burst
            'reliability': to_int(r['Reliability']),
            'vp': to_int(r['VP']) or 0,
            'tags': tags,
            'text': (r['Text'] or '').strip(),
            'flavor': (r['Flavor'] or '').strip(),
            'tier': (r['Tier'] or '').strip(),    # missions: "Tier 1".."Tier 3"
            'rewardCredits': to_int(r['RewardCredits']) or 0,
            'copies': to_int(r['Copies']) or 1,
            'art': None,
        }
        art = os.path.join(ART_SRC, cid + '.jpg')
        if os.path.exists(art):
            card['art'] = 'assets/art/%s.jpg' % cid
        cards[cid] = card

    os.makedirs(os.path.join(WEB, 'data'), exist_ok=True)
    with open(os.path.join(WEB, 'data', 'cards.json'), 'w', encoding='utf-8') as f:
        json.dump(cards, f, ensure_ascii=False, indent=1)

    # PHP copy of the same data
    os.makedirs(os.path.join(WEB, 'api', 'engine'), exist_ok=True)
    with open(os.path.join(WEB, 'api', 'engine', 'cards_data.php'), 'w', encoding='utf-8') as f:
        f.write("<?php\n// AUTO-GENERATED from cards/cards.csv by webGame/tools/build_data.py - do not edit.\n")
        f.write("function sar_cards_data(): array {\n  return json_decode(<<<'JSON'\n")
        f.write(json.dumps(cards, ensure_ascii=False))
        f.write("\nJSON, true);\n}\n")

    # assets
    art_dst = os.path.join(WEB, 'assets', 'art')
    icon_dst = os.path.join(WEB, 'assets', 'icons')
    os.makedirs(art_dst, exist_ok=True)
    os.makedirs(icon_dst, exist_ok=True)
    n_art = 0
    for cid in cards:
        src = os.path.join(ART_SRC, cid + '.jpg')
        if os.path.exists(src):
            shutil.copy2(src, os.path.join(art_dst, cid + '.jpg'))
            n_art += 1
    for name in os.listdir(ICON_SRC):
        if name.endswith('.png'):
            shutil.copy2(os.path.join(ICON_SRC, name), os.path.join(icon_dst, name))
    back = os.path.join(ROOT, 'cards', 'output', 'cards', 'card_back.png')
    if os.path.exists(back):
        shutil.copy2(back, os.path.join(WEB, 'assets', 'card_back.png'))

    total = sum(c['copies'] for c in cards.values())
    print('cards: %d unique, %d copies, %d with art' % (len(cards), total, n_art))


if __name__ == '__main__':
    main()
