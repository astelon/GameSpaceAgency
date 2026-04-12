-- ============================================================
-- SPACE AGENCY RACE — Tabletop Simulator Global Script
-- ============================================================
--
-- INITIAL SETUP (required before first use):
--   1. Host the 44 card PNGs from cards/output/cards/ at an
--      accessible URL (options below) and set BASE_IMAGE_URL.
--   2. Optionally provide a CARD_BACK_URL for a custom card back.
--   3. Paste this script into TTS: Scripting → Global Script.
--   4. Press "Save & Play" — the game will set up automatically.
--
-- IMAGE URL OPTIONS:
--   a) GitHub Raw  — push cards/output/cards/ to your repo, then:
--        https://raw.githubusercontent.com/USER/GameSpaceAgency/main/cards/output/cards/
--   b) GitHub Pages — enable Pages on your repo, then:
--        https://USER.github.io/GameSpaceAgency/cards/output/cards/
--   c) Local server — run: python -m http.server 8080 in the project root, then:
--        http://localhost:8080/cards/output/cards/
--      (works only when TTS and the server run on the same machine)
--
-- CARD IMAGE FILENAMES expected at BASE_IMAGE_URL:
--   template_tts_01.png  through  template_tts_44.png
--   (these are the files nanDECK wrote to cards/output/cards/)
--
-- ============================================================

-- ── CONFIGURATION ────────────────────────────────────────────

local BASE_IMAGE_URL = "https://raw.githubusercontent.com/astelon/GameSpaceAgency/main/cards/output/cards/"
-- Replace YOUR_USERNAME above with your GitHub username.

local CARD_BACK_URL  = BASE_IMAGE_URL .. "card_back.png"
-- Replace if you have a dedicated card-back image at a different URL.
-- A plain dark colour works too; you can use any publicly-hosted image.

-- Derived from BASE_IMAGE_URL by stripping the cards/output/cards/ suffix.
-- Update BASE_IMAGE_URL and this follows automatically.
local REPO_ROOT_URL    = BASE_IMAGE_URL:match("^(.*/)cards/output/cards/$") or BASE_IMAGE_URL
local RULEBOOK_PDF_URL = REPO_ROOT_URL .. "docs/rulebook.pdf"
local BOARD_IMAGE_URL  = REPO_ROOT_URL .. "tts/board.png"

-- ── CARD DATA ────────────────────────────────────────────────
-- card_index: sequential position in cards.csv → determines PNG filename
-- (template_tts_{card_index}.png)

local CARDS = {
    -- Engine cards (PNGs 1–7)
    { id="E01", name="Merlin-1a",              type="Engine",  tier="",       card_index=1,
      description="If this craft returns to Earth, return this card to hand.",
      cost=4, tags="Reusable;Experimental" },
    { id="E02", name="Sterling Booster",       type="Engine",  tier="",       card_index=2,
      description="High reliability, single-use.",
      cost=3, tags="Disposable;Reliable" },
    { id="E03", name="Hydrogen Core",          type="Engine",  tier="",       card_index=3,
      description="Requires Cryo Tank.",
      cost=5, tags="HighThrust;Cryogenic" },
    { id="E04", name="Ion Sustainer",          type="Engine",  tier="",       card_index=4,
      description="Low thrust but very reliable and efficient.",
      cost=2, tags="LowThrust;Efficient" },
    { id="E05", name="Hybrid Cycle",           type="Engine",  tier="",       card_index=5,
      description="If this craft returns to Earth, return this card to hand.",
      cost=4, tags="Reusable;Balanced" },
    { id="E06", name="Raptor-X",               type="Engine",  tier="",       card_index=6,
      description="High risk, high reward. May cause major failure on roll.",
      cost=6, tags="Experimental;HighThrust" },
    { id="E07", name="Kick Stage",             type="Engine",  tier="",       card_index=7,
      description="Stage: +2 Range for this launch. Discard after launch.",
      cost=4, tags="Disposable;Stageable" },

    -- Tank cards (PNGs 8–13)
    { id="T01", name="Standard Tank",          type="Tank",    tier="",       card_index=8,
      description="Compatible with most engines.",
      cost=2, tags="Stable" },
    { id="T02", name="Cryo Tank",              type="Tank",    tier="",       card_index=9,
      description="Required for Hydrogen Core engine.",
      cost=4, tags="Cryogenic;Extended" },
    { id="T03", name="Fuel Pod",               type="Tank",    tier="",       card_index=10,
      description="Stage: +1 Range for this launch. Discard after launch.",
      cost=1, tags="Cheap;Disposable;Stageable" },
    { id="T04", name="Expandable Tank",        type="Tank",    tier="",       card_index=11,
      description="Stage: +2 Range for this launch. Discard after launch.",
      cost=3, tags="Expandable;Stageable" },
    { id="T05", name="Pressurized Tank",       type="Tank",    tier="",       card_index=12,
      description="Safer for crewed payloads.",
      cost=3, tags="Pressurized" },
    { id="T06", name="Long-Range Tank",        type="Tank",    tier="",       card_index=13,
      description="Heavy. Needed for deep-space range.",
      cost=5, tags="DeepSpace;Extended" },

    -- Payload cards (PNGs 14–20)
    { id="P01", name="Comm Satellite",         type="Payload", tier="",       card_index=14,
      description="Remains on the board as an on-orbit asset.",
      cost=2, tags="Uncrewed;Electronics;Satellite" },
    { id="P02", name="Imaging Probe",          type="Payload", tier="",       card_index=15,
      description="Good for Earth/Moon recon missions.",
      cost=2, tags="Uncrewed;Scientific" },
    { id="P03", name="Science Module",         type="Payload", tier="",       card_index=16,
      description="High reward for deep-space research missions.",
      cost=3, tags="Scientific;Heavy" },
    { id="P04", name="Crew Capsule",           type="Payload", tier="",       card_index=17,
      description="Enables crewed missions. If this craft returns to Earth, return this card to hand.",
      cost=4, tags="Crewed;LifeSupport;Reusable" },
    { id="P05", name="CubeSat Cluster",        type="Payload", tier="",       card_index=18,
      description="Cheap, small experiments. Remains on the board as an on-orbit asset.",
      cost=1, tags="Uncrewed;Small;Satellite" },
    { id="P06", name="Landing Lander",         type="Payload", tier="",       card_index=19,
      description="Required for surface missions (Moon, Mars).",
      cost=3, tags="Surface;Heavy" },
    { id="P07", name="Cargo Return Capsule",   type="Payload", tier="",       card_index=20,
      description="If this craft returns to Earth, return this card to hand.",
      cost=3, tags="Uncrewed;Recovery;Reusable" },

    -- Support cards (PNGs 21–24)
    { id="S01", name="Heat Shield",            type="Support", tier="",       card_index=21,
      description="Use from Sub-Orbital to land safely. Discard after use.",
      cost=1, tags="HeatShield;Stageable" },
    { id="S02", name="Recovery Chutes",        type="Support", tier="",       card_index=22,
      description="Use from Sub-Orbital to land safely. Discard after use.",
      cost=1, tags="Parachute;Stageable" },
    { id="S03", name="Ceramic Tile Shield",    type="Support", tier="",       card_index=23,
      description="Use from Sub-Orbital to land safely. Return this card to hand if reused.",
      cost=2, tags="HeatShield;Reusable" },
    { id="S04", name="Guided Parafoil",        type="Support", tier="",       card_index=24,
      description="Use from Sub-Orbital to land safely. Return this card to hand if reused.",
      cost=2, tags="Parachute;Reusable" },

    -- Mission cards (PNGs 25–36)
    { id="M01", name="LEO Deployment",         type="Mission", tier="Tier 1", card_index=25,
      description="Requires: Payload with tag Uncrewed. Range 5.", vp=3, reward=2 },
    { id="M07", name="Emergency Resupply",     type="Mission", tier="Tier 1", card_index=31,
      description="Requires: Size M. Range 6.", vp=4, reward=3 },
    { id="M10", name="Capsule Recovery",       type="Mission", tier="Tier 1", card_index=34,
      description="Sub-Orbital → Earth. Requires: Light payload + Heat Shield or Parachute.", vp=5, reward=1 },
    { id="M11", name="Reusable Flight Test",   type="Mission", tier="Tier 1", card_index=35,
      description="Sub-Orbital → Earth. Requires: Reusable Payload + Reusable Landing support.", vp=4, reward=2 },
    { id="M02", name="Lunar Flyby",            type="Mission", tier="Tier 2", card_index=26,
      description="Requires: Range 8+.", vp=5, reward=2 },
    { id="M03", name="Lunar Landing",          type="Mission", tier="Tier 2", card_index=27,
      description="Requires: Landing Lander + Range 10.", vp=8, reward=3 },
    { id="M08", name="Science Relay",          type="Mission", tier="Tier 2", card_index=32,
      description="Requires: Scientific or Comm payload.", vp=6, reward=2 },
    { id="M09", name="Orbital Service Check",  type="Mission", tier="Tier 2", card_index=33,
      description="LEO → High Orbit → LEO. Requires: On-Orbit Satellite (no Engine needed).", vp=4, reward=2 },
    { id="M04", name="Mars Orbit Insertion",   type="Mission", tier="Tier 3", card_index=28,
      description="Requires: Range 14+, Size M or larger.", vp=12, reward=5 },
    { id="M05", name="Deep Space Probe",       type="Mission", tier="Tier 3", card_index=29,
      description="Requires: Range 16+.", vp=15, reward=6 },
    { id="M06", name="Crewed Station Visit",   type="Mission", tier="Tier 3", card_index=30,
      description="Requires: Crewed Capsule + Range 12.", vp=10, reward=4 },
    { id="M12", name="Lunar Sample Return",    type="Mission", tier="Tier 3", card_index=36,
      description="Requires: Cargo Return Capsule + Range 12 + Reusable Landing support.", vp=10, reward=5 },

    -- Tech cards (PNGs 37–40)
    { id="C01", name="Reusable Refurb",        type="Tech",    tier="",       card_index=37,
      description="Reusable engines gain +1 reliability.",
      cost=2, tags="Upgrade;Permanent" },
    { id="C02", name="Cryo Handling",          type="Tech",    tier="",       card_index=38,
      description="Allows Cryo Tank without penalty; +1 reliability for cryo setups.",
      cost=3, tags="Upgrade;Compatible" },
    { id="C03", name="Precision Guidance",     type="Tech",    tier="",       card_index=39,
      description="+1 effective reliability on launch checks.",
      cost=2, tags="Upgrade;Support" },
    { id="C04", name="Modular Payloads",       type="Tech",    tier="",       card_index=40,
      description="Treat your payload as one size lighter for mission requirements.",
      cost=2, tags="Upgrade;Flexible" },

    -- Event cards (PNGs 41–44)
    { id="EV01", name="Solar Storm",           type="Event",   tier="",       card_index=41,
      description="Global: All launches this round suffer -2 reliability." },
    { id="EV02", name="Funding Boost",         type="Event",   tier="",       card_index=42,
      description="All players gain +3 Credits immediately." },
    { id="EV03", name="Supply Delay",          type="Event",   tier="",       card_index=43,
      description="Players must spend +1 Credit to prepare launches this round." },
    { id="EV04", name="Tech Breakthrough",     type="Event",   tier="",       card_index=44,
      description="First player to launch this round draws a Tech card." },
}

-- ── CARD COLOURS (match card design) ─────────────────────────

local TYPE_COLOR = {
    Engine  = {0.27, 0.51, 0.71},   -- blue
    Tank    = {0.17, 0.63, 0.17},   -- green
    Payload = {1.00, 0.50, 0.05},   -- orange
    Support = {0.65, 0.55, 0.99},   -- purple-ish
    Mission = {0.84, 0.15, 0.15},   -- red
    Tech    = {0.77, 0.71, 0.99},   -- light purple
    Event   = {0.55, 0.34, 0.29},   -- brown
}

-- ── TABLE LAYOUT ─────────────────────────────────────────────
-- TTS axes: X = right (+) / left (−), Z = far (+) / near (−)
-- Deck component rail sits at the far end of the table (z ≈ +9).
-- Mission display runs across the centre (z ≈ +2 … +4).

local SPAWN_POSITIONS = {
    Engine       = { pos={-8,  1.5,  9},  rotY=0,  faceDown=false },
    Tank         = { pos={-4.5,1.5,  9},  rotY=0,  faceDown=false },
    Payload      = { pos={-1,  1.5,  9},  rotY=0,  faceDown=false },
    Support      = { pos={ 2.5,1.5,  9},  rotY=0,  faceDown=false },
    Tech         = { pos={ 6,  1.5,  9},  rotY=0,  faceDown=false },
    Event        = { pos={ 9,  1.5,  9},  rotY=0,  faceDown=true  },
    -- Mission decks by tier (draw piles, all face-down)
    ["Mission T1"] = { pos={-4,  1.5,  1},  rotY=0,  faceDown=true  },
    ["Mission T2"] = { pos={ 0,  1.5,  1},  rotY=0,  faceDown=true  },
    ["Mission T3"] = { pos={ 4,  1.5,  1},  rotY=0,  faceDown=true  },
}

-- Positions for the 3 revealed Tier-1 missions in the display row
local MISSION_DISPLAY_POSITIONS = {
    {-4, 1.5, -2},
    { 0, 1.5, -2},
    { 4, 1.5, -2},
}

-- Starting hand — one card of each type dealt to each player seat
local STARTING_CARDS = { "E02", "T01", "C03" }  -- Sterling Booster, Standard Tank, Precision Guidance

-- Player seat positions (Z near-side, facing -Z = toward camera)
local PLAYER_SEATS = {
    { color="White", pos={ 0,  1.5, -12}, rotY=0   },
    { color="Red",   pos={ 12, 1.5,  0},  rotY=270 },
    { color="Blue",  pos={ 0,  1.5,  15}, rotY=180 },
    { color="Green", pos={-12, 1.5,  0},  rotY=90  },
}

-- ── BOARD / TRACKER DATA ─────────────────────────────────────
-- Board: Custom_Board scale {24,1,15}, centre world {0,1.5,-1}
-- Pixel → world:  X = px_x * 0.0078125 − 12
--                 Z = px_y * 0.0078125 − 8.5  (includes board Z offset -1)

local BOARD_POS   = { x=0,  y=1.5, z=-1 }
local BOARD_SCALE = { x=24, y=1,   z=15 }

-- World-space centre of each orbital node (for craft token placement)
local ORBITAL_NODES = {
    Earth           = { x=-10.789, z=-2.563, name="Earth"            },
    SubOrbitalEarth = { x= -8.914, z=-2.563, name="Sub-Orbital Earth" },
    LEO             = { x= -6.922, z=-2.563, name="LEO"               },
    HighOrbit       = { x= -4.734, z=-2.563, name="High Orbit"        },
    MoonTransfer    = { x= -2.703, z=-4.398, name="Moon Transfer"     },
    MoonOrbit       = { x= -0.516, z=-5.648, name="Moon Orbit"        },
    SubOrbitalMoon  = { x=  1.711, z=-6.273, name="Sub-Orbital Moon"  },
    Moon            = { x=  3.938, z=-6.273, name="Moon"              },
    SolarOrbit      = { x= -2.703, z= -0.727, name="Solar Orbit"     },
    MarsTransfer    = { x= -0.516, z=  0.406, name="Mars Transfer"    },
    MarsOrbit       = { x=  1.672, z=  1.031, name="Mars Orbit"       },
    LMO             = { x=  3.820, z=  1.305, name="LMO"              },
    SubOrbitalMars  = { x=  5.930, z=  1.305, name="Sub-Orbital Mars" },
    Mars            = { x=  8.117, z=  1.305, name="Mars"             },
}

-- VP track: 31 positions (0–30).  SVG y=1635 → board.png row.
-- world_Z = 4.273,  world_X_i = -10.945 + i * 0.730
local VP_TRACK_Z    =  4.273
local VP_TRACK_X0   = -10.945
local VP_TRACK_STEP =  0.730

-- Credit track: 21 positions (0–20).  SVG y=1820 → board.png row.
-- world_Z = 5.719,  world_X_i = -10.828 + i * 0.938
local CREDIT_TRACK_Z    =  5.719
local CREDIT_TRACK_X0   = -10.828
local CREDIT_TRACK_STEP =  0.938
local CREDIT_START      =  5   -- each player begins with 5 Credits

-- Per-player tracker token colours (flat cylinders on the board tracks)
local PLAYER_TINTS = {
    { name="White", r=0.90, g=0.90, b=0.90 },
    { name="Red",   r=0.90, g=0.10, b=0.10 },
    { name="Blue",  r=0.15, g=0.35, b=1.00 },
    { name="Green", r=0.10, g=0.80, b=0.20 },
}

-- ── HELPERS ──────────────────────────────────────────────────

local function cardFaceURL(card_index)
    return BASE_IMAGE_URL .. string.format("template_tts_%02d.png", card_index)
end

-- Build the TTS JSON state table for a custom deck or single card.
-- cards      : list of CARD entries belonging to this deck
-- group_name : human-readable name (used for Nickname)
-- pos        : {x, y, z}
-- rotY       : Y rotation of the stack
-- face_down  : if true, deck spawns face-down (rotZ = 180)
local function buildDeckState(cards, group_name, pos, rotY, face_down)
    local rotZ       = face_down and 180 or 0
    local custom_deck = {}
    local deck_ids   = {}
    local contained  = {}
    local type_color = TYPE_COLOR[cards[1].type] or {0.71, 0.71, 0.71}

    for _, card in ipairs(cards) do
        local idx = card.card_index
        custom_deck[tostring(idx)] = {
            FaceURL      = cardFaceURL(idx),
            BackURL      = CARD_BACK_URL,
            NumWidth     = 1,
            NumHeight    = 1,
            BackIsHidden = true,
            UniqueBack   = false,
        }
        local card_id = idx * 100
        table.insert(deck_ids, card_id)

        -- Build description text
        local desc = card.description or ""
        if card.tags  and card.tags  ~= "" then desc = "[" .. card.tags .. "]\n" .. desc end
        if card.cost  and card.cost  ~= "" then desc = "Cost: " .. card.cost .. "\n"    .. desc end
        if card.tier  and card.tier  ~= "" then desc = card.tier .. "\n"               .. desc end
        if card.vp    and card.vp    ~= "" then desc = desc .. "\nVP: "    .. tostring(card.vp)    end
        if card.reward and card.reward ~= "" then desc = desc .. "  Credits: " .. tostring(card.reward) end

        table.insert(contained, {
            Name        = "Card",
            CardID      = card_id,
            Nickname    = card.name,
            Description = desc,
            GMNotes     = card.id,
            ColorDiffuse = { r=type_color[1], g=type_color[2], b=type_color[3] },
            CustomDeck  = { [tostring(idx)] = custom_deck[tostring(idx)] },
            Transform   = {
                posX=0, posY=0, posZ=0,
                rotX=0, rotY=180, rotZ=0,
                scaleX=1, scaleY=1, scaleZ=1,
            },
            Locked=false, Grid=true, Snap=true,
            Autoraise=true, Sticky=true, Tooltip=true,
        })
    end

    if #cards == 1 then
        -- TTS: a single card, not a deck wrapper
        local c = contained[1]
        c.Transform.posX  = pos[1]
        c.Transform.posY  = pos[2]
        c.Transform.posZ  = pos[3]
        c.Transform.rotY  = rotY or 0
        c.Transform.rotZ  = rotZ
        c.Nickname = group_name ~= cards[1].type and group_name or c.Nickname
        return c
    end

    return {
        Name     = "DeckCustom",
        Nickname = group_name,
        GMNotes  = "",
        ColorDiffuse = { r=type_color[1], g=type_color[2], b=type_color[3] },
        Transform = {
            posX  = pos[1], posY = pos[2], posZ = pos[3],
            rotX  = 0, rotY = rotY or 0, rotZ = rotZ,
            scaleX=1, scaleY=1, scaleZ=1,
        },
        Locked=false, Grid=true, Snap=true,
        Autoraise=true, Sticky=true, Tooltip=true,
        CustomDeck       = custom_deck,
        DeckIDs          = deck_ids,
        ContainedObjects = contained,
    }
end

-- ── SETUP LOGIC ──────────────────────────────────────────────

-- Collects cards by predicate into a list
local function filterCards(predicate)
    local result = {}
    for _, card in ipairs(CARDS) do
        if predicate(card) then table.insert(result, card) end
    end
    return result
end

-- Finds a single card by id
local function findCard(card_id)
    for _, card in ipairs(CARDS) do
        if card.id == card_id then return card end
    end
    return nil
end

-- Spawns a TTS object from a state table; returns the object.
local function spawnFromState(state)
    return spawnObjectJSON({ json=JSON.encode(state), sound=false, snap_to_grid=true })
end

-- Central game setup function.
local function setupGame()
    -- 1. Destroy all unlocked objects, plus any locked zone labels from a previous setup
    for _, obj in ipairs(getAllObjects()) do
        local name = obj.getName()
        if not obj.getLock() or name:sub(1, 10) == "ZoneLabel:" or name == "Orbital Map" then
            obj.destruct()
        end
    end

    Wait.time(function()
        -- 2. Component decks (Engine, Tank, Payload, Support, Tech)
        for _, deck_type in ipairs({"Engine","Tank","Payload","Support","Tech"}) do
            local cards = filterCards(function(c) return c.type == deck_type end)
            local sp    = SPAWN_POSITIONS[deck_type]
            spawnFromState(buildDeckState(cards, deck_type .. " Deck", sp.pos, sp.rotY, sp.faceDown))
        end

        -- 3. Event deck (face-down, shuffled)
        local event_cards = filterCards(function(c) return c.type == "Event" end)
        local ev_sp = SPAWN_POSITIONS["Event"]
        local event_obj = spawnFromState(buildDeckState(event_cards, "Event Deck", ev_sp.pos, ev_sp.rotY, ev_sp.faceDown))
        Wait.time(function() if event_obj then event_obj.shuffle() end end, 0.8)

        -- 4. Mission decks split by tier (each face-down & shuffled)
        for tier_key, tier_label in pairs({["Mission T1"]="Tier 1", ["Mission T2"]="Tier 2", ["Mission T3"]="Tier 3"}) do
            local tier_cards = filterCards(function(c) return c.type=="Mission" and c.tier==tier_label end)
            local sp = SPAWN_POSITIONS[tier_key]
            local deck_name = "Mission " .. tier_label
            local deck_obj = spawnFromState(buildDeckState(tier_cards, deck_name, sp.pos, sp.rotY, sp.faceDown))
            -- Shuffle only Tier 1 now; Tier 2 and 3 unlock later
            if tier_key == "Mission T1" then
                Wait.time(function()
                    if deck_obj then
                        deck_obj.shuffle()
                        -- 5. Reveal 3 Tier-1 mission cards face-up into the display row
                        Wait.time(function()
                            for i = 1, 3 do
                                if deck_obj then
                                    local dpos = MISSION_DISPLAY_POSITIONS[i]
                                    deck_obj.takeObject({
                                        position = {dpos[1], dpos[2] + 0.5, dpos[3]},
                                        flip     = true,  -- face-up
                                        smooth   = true,
                                    })
                                end
                            end
                        end, 0.8)
                    end
                end, 0.8)
            end
        end

        -- 6. Zone labels using Notecards
        local function placeLabel(text, pos)
            local nc = spawnObject({ type="Notecard", position=pos })
            nc.setName("ZoneLabel:" .. text)
            nc.setLock(true)
            nc.setValue(text)
        end
        placeLabel("ENGINES",         {-8,   0.8,  9})
        placeLabel("TANKS",           {-4.5, 0.8,  9})
        placeLabel("PAYLOADS",        {-1,   0.8,  9})
        placeLabel("SUPPORT",         { 2.5, 0.8,  9})
        placeLabel("TECH",            { 6,   0.8,  9})
        placeLabel("EVENTS",          { 9,   0.8,  9})
        placeLabel("TIER 1\nMISSIONS",{-4,   0.8,  1})
        placeLabel("TIER 2\nMISSIONS",{ 0,   0.8,  1})
        placeLabel("TIER 3\nMISSIONS",{ 4,   0.8,  1})
        placeLabel("MISSION DISPLAY", { 0,   0.8, -2})

        -- 7. Reliability dice — one d10 per player seat, placed in the centre
        local die_positions = { {-3, 1.5, -7}, {-1, 1.5, -7}, {1, 1.5, -7}, {3, 1.5, -7} }
        local die_colors    = { {1,1,1}, {1,0.2,0.2}, {0.2,0.4,1}, {0.2,0.8,0.2} }
        for i = 1, 4 do
            local dp = die_positions[i]
            local dc = die_colors[i]
            local die = spawnObject({ type="Die_10", position={dp[1], dp[2], dp[3]} })
            die.setColorTint({ r=dc[1], g=dc[2], b=dc[3] })
            die.setName("Reliability Die")
        end

        -- 8. Game board
        local board = spawnObject({
            type     = "Custom_Board",
            position = { BOARD_POS.x, BOARD_POS.y, BOARD_POS.z },
            scale    = { BOARD_SCALE.x, BOARD_SCALE.y, BOARD_SCALE.z },
        })
        board.setCustomObject({ image = BOARD_IMAGE_URL, image_secondary = BOARD_IMAGE_URL })
        board.setLock(true)
        board.setName("Orbital Map")

        -- 9. Player tracker tokens — flat cylinders stacked slightly on VP and Credit tracks
        for i, pc in ipairs(PLAYER_TINTS) do
            local y_off = 1.63 + (i - 1) * 0.04
            local vp_token = spawnObject({
                type     = "Cylinder",
                position = { VP_TRACK_X0, y_off, VP_TRACK_Z },
                scale    = { 0.45, 0.10, 0.45 },
            })
            vp_token.setColorTint({ r=pc.r, g=pc.g, b=pc.b })
            vp_token.setName("VP - " .. pc.name)

            local cr_token = spawnObject({
                type     = "Cylinder",
                position = { CREDIT_TRACK_X0 + CREDIT_START * CREDIT_TRACK_STEP, y_off, CREDIT_TRACK_Z },
                scale    = { 0.45, 0.10, 0.45 },
            })
            cr_token.setColorTint({ r=pc.r, g=pc.g, b=pc.b })
            cr_token.setName("Credits - " .. pc.name)
        end

        broadcastToAll("Space Agency Race is set up! Deal starting hands when all players are seated.", "Yellow")
    end, 0.3)
end

-- Deals 1 starting Engine, 1 Tank, and 1 Tech card to each seated player.
-- Starting cards are identified by their GMNotes field (stores the card id, e.g. "E02").
function dealStartingHands()
    local seated = {}
    for _, player in ipairs(Player.getPlayers()) do
        if player.seated then table.insert(seated, player) end
    end
    if #seated == 0 then
        broadcastToAll("No seated players found. Sit at a colour seat first.", "Red")
        return
    end

    -- Build a lookup: card_id → containing deck/card object
    local function findObjectByCardId(card_id)
        for _, obj in ipairs(getAllObjects()) do
            if obj.type == "Card" and obj.getGMNotes() == card_id then
                return obj, nil          -- stand-alone card
            elseif obj.type == "Deck" then
                for _, entry in ipairs(obj.getObjects()) do
                    if entry.gm_notes == card_id then
                        return obj, entry.guid  -- deck + guid of card within it
                    end
                end
            end
        end
        return nil, nil
    end

    local delay = 0
    for _, seat_player in ipairs(seated) do
        for _, card_id in ipairs(STARTING_CARDS) do
            Wait.time(function()
                local container, guid = findObjectByCardId(card_id)
                if not container then
                    broadcastToAll("Starting card " .. card_id .. " not found on table.", "Red")
                    return
                end
                local taken
                if guid then
                    taken = container.takeObject({ guid=guid, smooth=true })
                else
                    taken = container  -- it is already a loose card
                end
                if taken then taken.deal(1, seat_player.color) end
            end, delay)
            delay = delay + 0.3
        end
    end

    Wait.time(function()
        broadcastToAll(
            "Starting hands dealt! Each player receives: Sterling Booster + Standard Tank + Precision Guidance.",
            "Yellow")
    end, delay + 0.5)
end

-- ── BUTTONS ──────────────────────────────────────────────────

-- Creates a persistent clickable tile with a labelled button on it.
-- Returns the spawned tile object.
local function createButton(label, fn_name, position)
    local tile = spawnObject({
        type     = "BlockRectangle",
        position = position,
        scale    = {3.5, 0.2, 1.2},
    })
    tile.setLock(true)
    tile.setName(label)
    tile.setColorTint({0.1, 0.1, 0.3})
    tile.createButton({
        click_function = fn_name,
        function_owner = Global,
        label          = label,
        position       = {0, 0.6, 0},
        rotation       = {0, 0, 0},
        width          = 1800,
        height         = 500,
        font_size      = 220,
        color          = {0.9, 0.9, 0.9},
        hover_color    = {1, 1, 1},
        press_color    = {0.6, 0.8, 1},
    })
    return tile
end

-- Global button callback: reset / setup table
function onResetClicked(obj, player_color, alt_click)
    if alt_click then return end
    broadcastToAll(player_color .. " is resetting the table...", "Orange")
    setupGame()
end

-- Global button callback: deal starting hands
function onDealHandsClicked(obj, player_color, alt_click)
    if not alt_click then dealStartingHands() end
end

-- ── ENTRY POINT ──────────────────────────────────────────────

function onLoad(save_state)
    -- Spawn control buttons in a corner of the table
    Wait.time(function()
        createButton("Reset Table",          "onResetClicked",    {-14, 1.5, -10})
        createButton("Deal Starting Hands",  "onDealHandsClicked",{-14, 1.5, -12})

        -- Spawn the rulebook PDF once; survives resets (locked, not a ZoneLabel)
        local hasRulebook = false
        for _, obj in ipairs(getAllObjects()) do
            if obj.getName() == "Rulebook" then hasRulebook = true; break end
        end
        if not hasRulebook then
            local pdf = spawnObjectJSON({
                json = JSON.encode({
                    Name        = "Custom_PDF",
                    Nickname    = "Rulebook",
                    Description = "Space Agency Race — Rulebook",
                    Transform   = {
                        posX=12, posY=1.5, posZ=-6,
                        rotX=0,  rotY=0,  rotZ=0,
                        scaleX=2, scaleY=1, scaleZ=2,
                    },
                    CustomPDF = {
                        PDFUrl        = RULEBOOK_PDF_URL,
                        PDFPassword   = "",
                        PDFPage       = 0,
                        PDFPageOffset = 0,
                    },
                    Locked=false, Grid=true, Snap=true,
                    Autoraise=true, Sticky=true, Tooltip=true,
                }),
                sound = false,
                snap_to_grid = true,
            })
            if pdf then pdf.setLock(true) end
        end
    end, 1.0)

    -- Auto-setup only on a completely fresh table (no saved state)
    if save_state == nil or save_state == "" then
        Wait.time(setupGame, 1.5)
    end
end
