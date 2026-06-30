-- ============================================================
-- SPACE AGENCY RACE — Tabletop Simulator Global Script
-- ============================================================

local BASE_IMAGE_URL = "https://raw.githubusercontent.com/astelon/GameSpaceAgency/main/cards/output/cards/"
local CARD_BACK_URL = BASE_IMAGE_URL .. "card_back.png"
local REPO_ROOT_URL = BASE_IMAGE_URL:match("^(.*/)cards/output/cards/$") or BASE_IMAGE_URL
local BOARD_IMAGE_URL = REPO_ROOT_URL .. "tts/board_v2.png"
local SHIP_MODEL_URL = REPO_ROOT_URL .. "models/spaceship.obj"
local RULEBOOK_URL = REPO_ROOT_URL .. "docs/rulebook.html"

local MANAGED_NOTE = "GSA"
local STARTING_CARDS = { "E02", "T01", "S01" }
local LEVEL_COSTS = { [2] = 6, [3] = 14 }
local PLAYER_ORDER = { "White", "Red", "Blue", "Green" }

local PLAYER_TINTS = {
    White = { r = 0.92, g = 0.92, b = 0.92 },
    Red = { r = 0.92, g = 0.14, b = 0.14 },
    Blue = { r = 0.17, g = 0.38, b = 1.00 },
    Green = { r = 0.12, g = 0.78, b = 0.22 },
}

local TYPE_COLOR = {
    Engine = { 0.27, 0.51, 0.71 },
    Tank = { 0.17, 0.63, 0.17 },
    Payload = { 1.00, 0.50, 0.05 },
    Support = { 0.57, 0.38, 0.87 },
    Mission = { 0.84, 0.15, 0.15 },
    Tech = { 0.72, 0.64, 0.97 },
    Event = { 0.55, 0.34, 0.29 },
}

local BOARD_POS = { x = 0, y = 1.5, z = -1 }
local BOARD_TARGET_SIZE = { x = 24, y = 1, z = 15 }

local DECK_Y = 2.08
local BOARD_CARD_Y = 1.96
local BOARD_LABEL_Y = 1.84
local BOARD_MARKER_Y = 1.82
local TABLE_LABEL_Y = 1.03
local INFO_PANEL_Y = 1.06
local CONTROL_Y = 1.55

local DECK_LAYOUT = {
    component = { pos = { -14.8, DECK_Y, -6.8 }, rotY = 180, faceDown = true, name = "Component Deck" },
    event = { pos = { 14.8, DECK_Y, -6.8 }, rotY = 180, faceDown = true, name = "Event Deck" },
    mission = { pos = { 10.8, DECK_Y, -3.2 }, rotY = 90, faceDown = true, name = "Mission Deck" },
    tier2 = { pos = { 10.8, DECK_Y, 0.0 }, rotY = 90, faceDown = true, name = "Tier 2 Reserve" },
    tier3 = { pos = { 10.8, DECK_Y, 3.2 }, rotY = 90, faceDown = true, name = "Tier 3 Reserve" },
}

local MARKET_POSITIONS = {
    { -7.6, BOARD_CARD_Y, -7.1 },
    { -3.8, BOARD_CARD_Y, -7.1 },
    { 0.0, BOARD_CARD_Y, -7.1 },
    { 3.8, BOARD_CARD_Y, -7.1 },
    { 7.6, BOARD_CARD_Y, -7.1 },
}

local MISSION_DISPLAY_POSITIONS = {
    { -4.6, BOARD_CARD_Y, 2.25 },
    { 0.0, BOARD_CARD_Y, 2.25 },
    { 4.6, BOARD_CARD_Y, 2.25 },
}

local EVENT_DISPLAY_POS = { 0.0, BOARD_CARD_Y, -5.65 }

local TRANSFER_WINDOW_POSITIONS = {
    { 0.0, BOARD_MARKER_Y, -3.90 },
    { 0.0, BOARD_MARKER_Y, -2.80 },
    { 0.0, BOARD_MARKER_Y, -1.70 },
    { 0.0, BOARD_MARKER_Y, -0.60 },
    { 0.0, BOARD_MARKER_Y, 0.50 },
    { 0.0, BOARD_MARKER_Y, 1.60 },
}

local TRACKER_Y_BASE = 1.63
local TRACKER_Y_STEP = 0.04

local VP_TRACK = {
    x0 = -10.945,
    z = 4.273,
    step = 0.730,
    max = 30,
    prefix = "VP - ",
}

local CREDIT_TRACK = {
    x0 = -10.828,
    z = 5.719,
    step = 0.938,
    max = 20,
    prefix = "Credits - ",
    start = 5,
}

local CONTROL_LAYOUT = {
    origin = { -5.6, CONTROL_Y, 11.4 },
    columns = 5,
    columnStep = 2.85,
    rowStep = 0.95,
    tileScale = { 1.55, 0.18, 0.52 },
    buttonWidth = 920,
    buttonHeight = 240,
    fontSize = 108,
}

local PANEL_STYLES = {
    default = {
        scale = { 0.10, 0.08, 0.10 },
        width = 1050,
        height = 180,
        fontSize = 84,
        tileColor = { 0.02, 0.02, 0.03 },
        fontColor = { 0.93, 0.93, 0.96 },
        buttonColor = { 0, 0, 0, 0 },
        hoverColor = { 0, 0, 0, 0 },
        pressColor = { 0, 0, 0, 0 },
    },
    display = {
        scale = { 0.10, 0.08, 0.10 },
        width = 1220,
        height = 220,
        fontSize = 90,
        tileColor = { 0.02, 0.02, 0.03 },
        fontColor = { 0.95, 0.91, 0.76 },
        buttonColor = { 0, 0, 0, 0 },
        hoverColor = { 0, 0, 0, 0 },
        pressColor = { 0, 0, 0, 0 },
    },
    agency = {
        scale = { 0.10, 0.08, 0.10 },
        width = 980,
        height = 210,
        fontSize = 80,
        tileColor = { 0.02, 0.02, 0.03 },
        fontColor = { 0.93, 0.93, 0.96 },
        buttonColor = { 0, 0, 0, 0 },
        hoverColor = { 0, 0, 0, 0 },
        pressColor = { 0, 0, 0, 0 },
    },
    info = {
        scale = { 0.10, 0.08, 0.10 },
        width = 1700,
        height = 320,
        fontSize = 88,
        tileColor = { 0.02, 0.02, 0.03 },
        fontColor = { 0.93, 0.93, 0.96 },
        buttonColor = { 0, 0, 0, 0 },
        hoverColor = { 0, 0, 0, 0 },
        pressColor = { 0, 0, 0, 0 },
    },
    status = {
        scale = { 0.10, 0.08, 0.10 },
        width = 2200,
        height = 980,
        fontSize = 84,
        tileColor = { 0.02, 0.02, 0.03 },
        fontColor = { 0.93, 0.93, 0.96 },
        buttonColor = { 0, 0, 0, 0 },
        hoverColor = { 0, 0, 0, 0 },
        pressColor = { 0, 0, 0, 0 },
    },
}

local CONTROL_BUTTONS = {
    { name = "Reset Table", label = "Reset\nTable", callback = "onResetClicked" },
    { name = "Deal Starting Hands", label = "Deal\nStart", callback = "onDealHandsClicked" },
    { name = "Planning Phase", label = "Planning", callback = "onPlanningPhaseClicked" },
    { name = "Maintenance", label = "Mainte-\nnance", callback = "onMaintenanceClicked" },
    { name = "Increase Agency Level", label = "Level\nUp", callback = "onLevelUpClicked" },
    { name = "Refill Market", label = "Refill\nMarket", callback = "onRefillMarketClicked" },
    { name = "Refill Missions", label = "Refill\nMissions", callback = "onRefillMissionsClicked" },
    { name = "Buy Sterling Booster", label = "Buy\nSterling", callback = "onBuySterlingClicked" },
    { name = "Buy Standard Tank", label = "Buy\nTank", callback = "onBuyTankClicked" },
    { name = "Buy Heat Shield", label = "Buy\nShield", callback = "onBuyShieldClicked" },
}

local ROUND_STATUS_POS = { 12.6, INFO_PANEL_Y, 10.1 }
local RULEBOOK_NOTE_POS = { -12.6, INFO_PANEL_Y, 10.1 }
local CRAFT_BAG_POS = { -14.8, 1.5, -3.6 }
local FIRST_PLAYER_POS = { -14.8, 1.5, -1.0 }

local RELIABILITY_DICE = {
    { color = "White", pos = { -6.0, 1.5, 7.5 } },
    { color = "Red", pos = { -2.0, 1.5, 7.5 } },
    { color = "Blue", pos = { 2.0, 1.5, 7.5 } },
    { color = "Green", pos = { 6.0, 1.5, 7.5 } },
}

local AGENCY_LEVEL_TRACKS = {
    White = {
        { -1.6, 1.14, -10.2 },
        { 0.0, 1.14, -10.2 },
        { 1.6, 1.14, -10.2 },
    },
    Red = {
        { 13.9, 1.14, -1.6 },
        { 13.9, 1.14, 0.0 },
        { 13.9, 1.14, 1.6 },
    },
    Blue = {
        { 1.6, 1.14, 9.4 },
        { 0.0, 1.14, 9.4 },
        { -1.6, 1.14, 9.4 },
    },
    Green = {
        { -13.9, 1.14, 1.6 },
        { -13.9, 1.14, 0.0 },
        { -13.9, 1.14, -1.6 },
    },
}

local AGENCY_LABEL_POSITIONS = {
    White = { 0.0, TABLE_LABEL_Y, -11.45 },
    Red = { 14.65, TABLE_LABEL_Y, 0.0 },
    Blue = { 0.0, TABLE_LABEL_Y, 10.65 },
    Green = { -14.65, TABLE_LABEL_Y, 0.0 },
}

local CARD_LABELS = {
    { name = "Card Market Label", text = "Card Market\n5 face-up", pos = { 0.0, BOARD_LABEL_Y, -8.55 }, style = "display" },
    { name = "Event Display Label", text = "Active Event", pos = { 0.0, BOARD_LABEL_Y, -6.95 }, style = "display" },
    { name = "Mission Display Label", text = "Mission Display\n3 face-up", pos = { 0.0, BOARD_LABEL_Y, 0.95 }, style = "display" },
}

local OBSOLETE_LABEL_NAMES = {
    "Component Deck Label",
    "Event Deck Label",
    "Mission Deck Label",
    "Tier 2 Reserve Label",
    "Tier 3 Reserve Label",
    "Craft Bag Label",
    "First Player Label",
    "Agency Track - White",
    "Agency Track - Red",
    "Agency Track - Blue",
    "Agency Track - Green",
}

local SUPPLY_RECOVERY_LAYOUTS = {
    component = {
        target = DECK_LAYOUT.component.pos,
        rotation = { 0, DECK_LAYOUT.component.rotY, 180 },
        anchors = {
            { -12.8, 1.6, -9.7 },
            { -16.0, DECK_Y, -8.8 },
            DECK_LAYOUT.component.pos,
        },
    },
    event = {
        target = DECK_LAYOUT.event.pos,
        rotation = { 0, DECK_LAYOUT.event.rotY, 180 },
        anchors = {
            { 12.8, 1.6, -9.7 },
            { 16.0, DECK_Y, -8.8 },
            DECK_LAYOUT.event.pos,
        },
    },
    mission = {
        target = DECK_LAYOUT.mission.pos,
        rotation = { 0, DECK_LAYOUT.mission.rotY, 180 },
        anchors = {
            { 13.2, 1.6, -3.2 },
            { 14.8, DECK_Y, -3.6 },
            { 16.1, DECK_Y, -3.4 },
            DECK_LAYOUT.mission.pos,
        },
    },
    tier2 = {
        target = DECK_LAYOUT.tier2.pos,
        rotation = { 0, DECK_LAYOUT.tier2.rotY, 180 },
        anchors = {
            { 13.2, 1.6, 0.0 },
            { 14.8, DECK_Y, -0.6 },
            { 16.1, DECK_Y, -0.2 },
            DECK_LAYOUT.tier2.pos,
        },
    },
    tier3 = {
        target = DECK_LAYOUT.tier3.pos,
        rotation = { 0, DECK_LAYOUT.tier3.rotY, 180 },
        anchors = {
            { 13.2, 1.6, 3.2 },
            { 14.8, DECK_Y, 2.4 },
            { 16.1, DECK_Y, 3.0 },
            DECK_LAYOUT.tier3.pos,
        },
    },
}

local LEGACY_EXACT_NAMES = {
    ["Orbital Map"] = true,
    ["Rulebook"] = true,
    ["Ship Tokens"] = true,
}

local CARDS = {
    { id = "E01", name = "Merlin-1a", type = "Engine", missionType = nil, tier = nil, card_index = 1, copies = 3, cost = 4, thrust = 7, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 7, vp = 0, reward = nil, tags = "Reusable;Experimental", text = "If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "E02", name = "Sterling Booster", type = "Engine", missionType = nil, tier = nil, card_index = 4, copies = 8, cost = 3, thrust = 5, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 9, vp = 0, reward = nil, tags = "Disposable;Reliable;Basic", text = "High reliability, single-use. Always available for purchase.", isBasic = true },
    { id = "E03", name = "Hydrogen Core", type = "Engine", missionType = nil, tier = nil, card_index = 12, copies = 2, cost = 5, thrust = 8, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 7, vp = 0, reward = nil, tags = "HighThrust;Cryogenic", text = "Requires at least one Cryo Tank. Cryo propellant delivers high thrust and long Range per unit mass.", isBasic = false },
    { id = "E04", name = "Ion Sustainer", type = "Engine", missionType = nil, tier = nil, card_index = 14, copies = 3, cost = 2, thrust = 3, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 9, vp = 0, reward = nil, tags = "LowThrust;Efficient", text = "Low thrust but very reliable and efficient.", isBasic = false },
    { id = "E05", name = "Hybrid Cycle", type = "Engine", missionType = nil, tier = nil, card_index = 17, copies = 3, cost = 4, thrust = 6, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 8, vp = 0, reward = nil, tags = "Reusable;Balanced", text = "If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "E06", name = "Raptor-X", type = "Engine", missionType = nil, tier = nil, card_index = 20, copies = 2, cost = 6, thrust = 9, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 6, vp = 0, reward = nil, tags = "Experimental;HighThrust", text = "Non-Reusable. Highest thrust available. Best for mass-heavy payloads where no other engine qualifies — but lower Reliability means a higher chance of losing it on a failed roll.", isBasic = false },
    { id = "E07", name = "Kick Stage", type = "Engine", missionType = nil, tier = nil, card_index = 22, copies = 3, cost = 4, thrust = 7, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = 7, vp = 0, reward = nil, tags = "Disposable;Stageable", text = "Stage: +2 Range for this launch. Still counts as your Engine for this mission. Discard after launch.", isBasic = false },
    { id = "T01", name = "Standard Tank", type = "Tank", missionType = nil, tier = nil, card_index = 25, copies = 8, cost = 2, thrust = nil, range = 5, mass = 2, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Stable;Basic", text = "Compatible with most engines. Always available for purchase.", isBasic = true },
    { id = "T02", name = "Cryo Tank", type = "Tank", missionType = nil, tier = nil, card_index = 33, copies = 3, cost = 4, thrust = nil, range = 8, mass = 3, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Cryogenic;Extended", text = "Required for Hydrogen Core engine.", isBasic = false },
    { id = "T03", name = "Fuel Pod", type = "Tank", missionType = nil, tier = nil, card_index = 36, copies = 5, cost = 1, thrust = nil, range = 3, mass = 1, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Cheap;Disposable;Stageable", text = "Stage: +1 Range for this launch. Discard after launch.", isBasic = false },
    { id = "T04", name = "Expandable Tank", type = "Tank", missionType = nil, tier = nil, card_index = 41, copies = 4, cost = 3, thrust = nil, range = 5, mass = 2, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Expandable;Stageable", text = "Stage: +2 Range for this launch. Discard after launch.", isBasic = false },
    { id = "T05", name = "Pressurized Tank", type = "Tank", missionType = nil, tier = nil, card_index = 45, copies = 3, cost = 2, thrust = nil, range = 5, mass = 2, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Pressurized", text = "Safer for crewed payloads.", isBasic = false },
    { id = "T06", name = "Long-Range Tank", type = "Tank", missionType = nil, tier = nil, card_index = 48, copies = 2, cost = 5, thrust = nil, range = 12, mass = 4, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "DeepSpace;Extended", text = "Range 12. Heavy. Mass 4.", isBasic = false },
    { id = "P01", name = "Comm Satellite", type = "Payload", missionType = nil, tier = nil, card_index = 50, copies = 3, cost = 2, thrust = nil, range = nil, mass = 2, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Uncrewed;Electronics;Satellite", text = "After delivery to LEO or High Orbit (GEO), this payload remains on the board as a communications satellite. Once per round, spend 1 Energy to gain 1 Credit from relay contracts.", isBasic = false },
    { id = "P02", name = "Imaging Probe", type = "Payload", missionType = nil, tier = nil, card_index = 53, copies = 3, cost = 2, thrust = nil, range = nil, mass = 1, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Uncrewed;Scientific;Electronics;Satellite", text = "After delivery, this payload remains on the board as a small science satellite. Once per round, spend 1 Energy to gain 1 VP from imagery or research data.", isBasic = false },
    { id = "P03", name = "Science Module", type = "Payload", missionType = nil, tier = nil, card_index = 56, copies = 3, cost = 3, thrust = nil, range = nil, mass = 3, energy = -2, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Scientific;Heavy", text = "Spend 2 Energy to activate this module's instruments. High reward for deep-space research missions.", isBasic = false },
    { id = "P04", name = "Crew Capsule", type = "Payload", missionType = nil, tier = nil, card_index = 59, copies = 3, cost = 4, thrust = nil, range = nil, mass = 2, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Crewed;LifeSupport;Reusable", text = "Spend 1 Energy when this capsule launches or relaunches from a surface. Enables crewed missions. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "P05", name = "CubeSat Cluster", type = "Payload", missionType = nil, tier = nil, card_index = 62, copies = 4, cost = 1, thrust = nil, range = nil, mass = 1, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Uncrewed;Small;Satellite", text = "Cheap, small experiments. Remains on the board as an on-orbit asset.", isBasic = false },
    { id = "P06", name = "Landing Lander", type = "Payload", missionType = nil, tier = nil, card_index = 66, copies = 3, cost = 3, thrust = nil, range = nil, mass = 2, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Surface;Heavy", text = "Enables surface landing. Alternative: use Rocket-as-Lander (Engine + sufficient Range).", isBasic = false },
    { id = "P07", name = "Cargo Return Capsule", type = "Payload", missionType = nil, tier = nil, card_index = 69, copies = 3, cost = 3, thrust = nil, range = nil, mass = 1, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Uncrewed;Recovery;Reusable", text = "Useful for recovery or sample-return missions. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "P08", name = "Station Hub", type = "Payload", missionType = nil, tier = nil, card_index = 137, copies = 2, cost = 4, thrust = nil, range = nil, mass = 2, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Station;Docking;Electronics", text = "Core bus for a permanent orbital station. If this craft ends movement at High Orbit (GEO) with a Power card, a LifeSupport card, and one additional Scientific or Electronics card attached, designate it as an On-Orbit Station. Once per round, spend 1 Energy to gain 1 Credit from station operations.", isBasic = false },
    { id = "S01", name = "Heat Shield", type = "Support", missionType = nil, tier = nil, card_index = 72, copies = 6, cost = 1, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Heat Shield;Stageable;Basic", text = "Landing support. Use from a Sub-Orbital node to land safely. Discard after use. Always available for purchase.", isBasic = true },
    { id = "S02", name = "Recovery Chutes", type = "Support", missionType = nil, tier = nil, card_index = 78, copies = 4, cost = 1, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Parachute;Stageable", text = "Landing support. Use from a Sub-Orbital node to land safely. Discard after use.", isBasic = false },
    { id = "S03", name = "Ceramic Tile Shield", type = "Support", missionType = nil, tier = nil, card_index = 82, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Heat Shield;Reusable", text = "Landing support. Use from a Sub-Orbital node to land safely. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "S04", name = "Guided Parafoil", type = "Support", missionType = nil, tier = nil, card_index = 85, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Parachute;Reusable", text = "Landing support. Use from a Sub-Orbital node to land safely. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "M01", name = "LEO Deployment", type = "Mission", missionType = "Public", tier = "Tier 1", card_index = 88, copies = 1, cost = 0, thrust = nil, range = 2, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 2, reward = 5, tags = "LEO;Commercial;Public", text = "Reach LEO.<br>Carry an Uncrewed payload.", isBasic = false },
    { id = "M02", name = "Lunar Flyby", type = "Mission", missionType = "Public", tier = "Tier 2", card_index = 89, copies = 1, cost = 0, thrust = nil, range = 10, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 7, reward = 2, tags = "Lunar;Scientific;Prestige;Public", text = "Reach Moon Orbit and return to Earth.<br>Total route range needed: 10.", isBasic = false },
    { id = "M03", name = "Lunar Landing", type = "Mission", missionType = "Public", tier = "Tier 2", card_index = 90, copies = 1, cost = 0, thrust = nil, range = 7, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 9, reward = 3, tags = "Lunar;Surface;Prestige;Public", text = "Reach the Moon surface (one-way).<br>Have Landing Lander or Rocket-as-Lander.", isBasic = false },
    { id = "M04", name = "Mars Orbit Insertion", type = "Mission", missionType = "Public", tier = "Tier 3", card_index = 91, copies = 1, cost = 0, thrust = nil, range = 7, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 13, reward = 3, tags = "Mars;DeepSpace;Prestige;Public", text = "Reach Mars High Orbit.<br>Add Transfer Window cost to route plan.<br>Carry payload Mass 2+.", isBasic = false },
    { id = "M05", name = "Deep Space Probe", type = "Mission", missionType = "Public", tier = "Tier 3", card_index = 92, copies = 1, cost = 0, thrust = nil, range = 9, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 15, reward = 2, tags = "DeepSpace;Scientific;Prestige;Public", text = "Reach Sub-Orbital Mars.<br>Add Transfer Window cost to route plan.<br>Have Scientific payload + Sensor Array.<br>Spend 2 Energy for mission ops.", isBasic = false },
    { id = "M06", name = "Crewed Station Visit", type = "Mission", missionType = "Public", tier = "Tier 2", card_index = 93, copies = 1, cost = 0, thrust = nil, range = 6, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 5, reward = 4, tags = "Crewed;Docking;On-Orbit;Infrastructure;Public", text = "Reach High Orbit (GEO), dock with any On-Orbit Station, and return to Earth.<br>Have Crewed Capsule + Docking card + Engine.", isBasic = false },
    { id = "M07", name = "Emergency Resupply", type = "Mission", missionType = "Public", tier = "Tier 1", card_index = 94, copies = 1, cost = 0, thrust = nil, range = 4, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 3, reward = 6, tags = "LEO;Commercial;Public", text = "Reach LEO and return to Earth.<br>Carry payload Mass 2+.", isBasic = false },
    { id = "M08", name = "Science Relay", type = "Mission", missionType = "Public", tier = "Tier 2", card_index = 95, copies = 1, cost = 0, thrust = nil, range = 6, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 6, reward = 3, tags = "Scientific;Relay;Infrastructure;Public", text = "Reach High Orbit and return to Earth.<br>Have Scientific or Comm payload.<br>Spend 1 Energy to run relay instruments.", isBasic = false },
    { id = "M09", name = "Orbital Service Check", type = "Mission", missionType = "Public", tier = "Tier 2", card_index = 96, copies = 1, cost = 0, thrust = nil, range = 2, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 2, reward = 6, tags = "On-Orbit;Commercial;Public", text = "From a craft at LEO, fly to High Orbit (GEO) and return to LEO.<br>Requires: one of your Satellites already in High Orbit (GEO) to inspect. Engine with sufficient Range.", isBasic = false },
    { id = "M10", name = "Capsule Recovery", type = "Mission", missionType = "Public", tier = "Tier 1", card_index = 97, copies = 1, cost = 0, thrust = nil, range = 1, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 3, reward = 2, tags = "In-Flight;Infrastructure;Public", text = "From Sub-Orbital Earth, land at Earth.<br>Carry payload Mass 1 + Heat Shield or Parachute.", isBasic = false },
    { id = "M11", name = "Reusable Flight Test", type = "Mission", missionType = "Public", tier = "Tier 1", card_index = 98, copies = 1, cost = 0, thrust = nil, range = 2, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 4, reward = 3, tags = "Recovery;Infrastructure;Public", text = "Fly Earth -> Sub-Orbital Earth -> Earth.<br>Use Reusable Payload + Reusable reentry support.", isBasic = false },
    { id = "M12", name = "Lunar Sample Return", type = "Mission", missionType = "Public", tier = "Tier 3", card_index = 99, copies = 1, cost = 0, thrust = nil, range = 14, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 13, reward = 3, tags = "Lunar;Recovery;Prestige;Public", text = "Reach Moon surface, collect samples, and return to Earth.<br>Have Lander (or Rocket-as-Lander) + Cargo Return Capsule + Earth reentry support.", isBasic = false },
    { id = "C01", name = "Reusable Refurb", type = "Tech", missionType = nil, tier = nil, card_index = 100, copies = 2, cost = 2, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Upgrade;Permanent", text = "Your Reusable engines gain +1 Reliability. When you recover a Reusable card during Maintenance, gain 1 Credit.", isBasic = false },
    { id = "C02", name = "Cryo Handling", type = "Tech", missionType = nil, tier = nil, card_index = 102, copies = 2, cost = 3, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Upgrade;Compatible", text = "Your rockets that include a Cryo Tank gain +1 Reliability on launch checks.", isBasic = false },
    { id = "C03", name = "Precision Guidance", type = "Tech", missionType = nil, tier = nil, card_index = 104, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Upgrade;Support", text = "Increase mission success chance: +1 effective reliability on launch checks.", isBasic = false },
    { id = "C04", name = "Modular Payloads", type = "Tech", missionType = nil, tier = nil, card_index = 107, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Upgrade;Flexible", text = "Reduce your payload's Mass by 1 (minimum 1) for Thrust checks.", isBasic = false },
    { id = "EV01", name = "Solar Storm", type = "Event", missionType = nil, tier = nil, card_index = 110, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "Global: All launches this round suffer -2 reliability.", isBasic = false },
    { id = "EV02", name = "Funding Boost", type = "Event", missionType = nil, tier = nil, card_index = 111, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "Occasional bonus: All players gain +3 Credits immediately.", isBasic = false },
    { id = "EV03", name = "Supply Delay", type = "Event", missionType = nil, tier = nil, card_index = 112, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "Players must spend +1 Credit to prepare launches this round.", isBasic = false },
    { id = "EV04", name = "Tech Breakthrough", type = "Event", missionType = nil, tier = nil, card_index = 113, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "First player to launch this round searches the Component Deck for 1 Technology card (add to hand, reshuffle).", isBasic = false },
    { id = "S05", name = "Docking Adapter", type = "Support", missionType = nil, tier = nil, card_index = 114, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Docking;Reusable", text = "Enables docking with stations and other craft. Required for Docking missions. Spend 1 Energy to dock. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "S06", name = "Orbital Tug", type = "Support", missionType = nil, tier = nil, card_index = 117, copies = 2, cost = 3, thrust = nil, range = nil, mass = nil, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Docking;Maneuver", text = "Enables docking and orbital maneuvering (satisfies Docking and Maneuver tag requirements). Spend 1 Energy when activating this craft in orbit to gain +1 Range. If this craft returns to Earth, return this card to hand.", isBasic = false },
    { id = "EV05", name = "Docking Opportunity", type = "Event", missionType = nil, tier = nil, card_index = 119, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "This round, any craft with a Docking tag that docks with an On-Orbit Station in High Orbit (GEO) gains +2 VP.", isBasic = false },
    { id = "EV06", name = "Transfer Window Storm", type = "Event", missionType = nil, tier = nil, card_index = 120, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "This round, Transfer Window cost is increased by +2 (max TW 5).", isBasic = false },
    { id = "EV07", name = "Launch Window", type = "Event", missionType = nil, tier = nil, card_index = 121, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "This round, Transfer Window cost is reduced by 2 (min TW 0).", isBasic = false },
    { id = "EV08", name = "Expanded Operations", type = "Event", missionType = nil, tier = nil, card_index = 122, copies = 1, cost = 0, thrust = nil, range = nil, mass = nil, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "Event", text = "This round, each player's hand limit is increased to 7.", isBasic = false },
    { id = "S07", name = "Solar Panel", type = "Support", missionType = nil, tier = nil, card_index = 123, copies = 3, cost = 1, thrust = nil, range = nil, mass = nil, energy = 2, energyMode = "Gen", reliability = nil, vp = 0, reward = nil, tags = "Power;Solar;Fragile", text = "Generates 2 Energy at the start of each Action Phase while this craft is in space. If this craft enters atmosphere, discard this card.", isBasic = false },
    { id = "S08", name = "RTG", type = "Support", missionType = nil, tier = nil, card_index = 126, copies = 2, cost = 3, thrust = nil, range = nil, mass = 1, energy = 3, energyMode = "Gen", reliability = nil, vp = 0, reward = nil, tags = "Power;DeepSpace;Heavy", text = "Generates 3 Energy at the start of each Action Phase. Works in any location. This card's Mass counts toward launch Thrust checks.", isBasic = false },
    { id = "S09", name = "Battery Pack", type = "Support", missionType = nil, tier = nil, card_index = 128, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = 4, energyMode = "Storage", reliability = nil, vp = 0, reward = nil, tags = "Power;Storage", text = "Enters play with 4 stored Energy. Does not generate Energy. At the end of the round, you may store 1 surplus generated Energy on this card, up to 4.", isBasic = false },
    { id = "S10", name = "Flight Computer", type = "Support", missionType = nil, tier = nil, card_index = 131, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Electronics;Guidance", text = "Spend 1 Energy when this craft launches, docks, or relaunches from a surface: this craft gets +1 Reliability for that check.", isBasic = false },
    { id = "S11", name = "Sensor Array", type = "Support", missionType = nil, tier = nil, card_index = 134, copies = 3, cost = 2, thrust = nil, range = nil, mass = nil, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Scientific;Electronics", text = "Spend 1 Energy to activate this card. Required for Tier 3 Scientific missions and any mission text that requires Sensors.", isBasic = false },
    { id = "S12", name = "Habitation Ring", type = "Support", missionType = nil, tier = nil, card_index = 139, copies = 2, cost = 3, thrust = nil, range = nil, mass = 1, energy = nil, energyMode = nil, reliability = nil, vp = 0, reward = nil, tags = "LifeSupport;Station", text = "Provides living quarters for long-duration crews. If attached to a Station Hub in High Orbit (GEO), it helps that craft qualify as an On-Orbit Station.", isBasic = false },
    { id = "S13", name = "Microgravity Lab", type = "Support", missionType = nil, tier = nil, card_index = 141, copies = 2, cost = 3, thrust = nil, range = nil, mass = 1, energy = -1, energyMode = "Use", reliability = nil, vp = 0, reward = nil, tags = "Scientific;Station", text = "Orbital laboratory module. Spend 1 Energy to activate experiments; if attached to a Station Hub in High Orbit (GEO), gain 1 VP the first time you do this each round. It also helps that craft qualify as an On-Orbit Station.", isBasic = false },
}

local CARD_BY_ID = {}
for _, card in ipairs(CARDS) do
    CARD_BY_ID[card.id] = card
end

local gameState = {}

local function cloneTable(source)
    local result = {}
    for key, value in pairs(source) do
        result[key] = value
    end
    return result
end

local function startsWith(value, prefix)
    return type(value) == "string" and value:sub(1, #prefix) == prefix
end

local function clamp(value, minValue, maxValue)
    if value < minValue then
        return minValue
    end
    if value > maxValue then
        return maxValue
    end
    return value
end

local function isPlayableColor(color)
    return PLAYER_TINTS[color] ~= nil
end

local function defaultAgencyLevels()
    local levels = {}
    for _, color in ipairs(PLAYER_ORDER) do
        levels[color] = 1
    end
    return levels
end

local function normalizeState(state)
    state = state or {}
    state.currentRound = tonumber(state.currentRound) or 0
    state.transferWindowBase = clamp(tonumber(state.transferWindowBase) or 0, 0, 5)
    state.activeEventId = state.activeEventId
    state.activeEventName = state.activeEventName
    state.unlockedTier2 = state.unlockedTier2 == true
    state.unlockedTier3 = state.unlockedTier3 == true
    state.agencyLevels = state.agencyLevels or defaultAgencyLevels()

    for _, color in ipairs(PLAYER_ORDER) do
        state.agencyLevels[color] = clamp(tonumber(state.agencyLevels[color]) or 1, 1, 3)
    end

    return state
end

local function resetState()
    gameState = normalizeState({
        currentRound = 0,
        transferWindowBase = 0,
        activeEventId = nil,
        activeEventName = nil,
        unlockedTier2 = false,
        unlockedTier3 = false,
        agencyLevels = defaultAgencyLevels(),
    })
end

local function getTag(obj)
    return (obj and (obj.tag or obj.type)) or ""
end

local function isCard(obj)
    return getTag(obj) == "Card"
end

local function isDeck(obj)
    local tag = getTag(obj)
    return tag == "Deck" or tag == "DeckCustom"
end

local function isCardStack(obj)
    return isCard(obj) or isDeck(obj)
end

local function distanceSquared(pos, target)
    local dx = pos.x - target[1]
    local dy = pos.y - target[2]
    local dz = pos.z - target[3]
    return dx * dx + dy * dy + dz * dz
end

local function getManagedNote(obj)
    if obj and obj.getGMNotes then
        return obj.getGMNotes() or ""
    end
    return ""
end

local function isLegacyManagedName(name)
    if LEGACY_EXACT_NAMES[name] then
        return true
    end
    return startsWith(name, "ZoneLabel:")
end

local function markManaged(obj)
    if obj and obj.setGMNotes then
        obj.setGMNotes(MANAGED_NOTE)
    end
    return obj
end

local function findObjectByName(name)
    for _, obj in ipairs(getAllObjects()) do
        if obj.getName() == name then
            return obj
        end
    end
    return nil
end

local function findCardStackNear(position, radius)
    local best = nil
    local bestDistance = nil
    local radiusSq = radius * radius

    for _, obj in ipairs(getAllObjects()) do
        if isCardStack(obj) then
            local pos = obj.getPosition()
            local dist = distanceSquared(pos, position)
            if dist <= radiusSq and (bestDistance == nil or dist < bestDistance) then
                best = obj
                bestDistance = dist
            end
        end
    end

    return best
end

local function findLooseCardByIdNearPositions(cardId, positions, radius)
    local radiusSq = radius * radius
    for _, obj in ipairs(getAllObjects()) do
        if isCard(obj) and obj.getGMNotes() == cardId then
            local pos = obj.getPosition()
            for _, target in ipairs(positions) do
                if distanceSquared(pos, target) <= radiusSq then
                    return obj
                end
            end
        end
    end
    return nil
end

local function findCardInDeckById(cardId, deckPos)
    local stack = findCardStackNear(deckPos, 2.2)
    if not stack then
        return nil, nil
    end

    if isDeck(stack) then
        for _, entry in ipairs(stack.getObjects()) do
            if entry.gm_notes == cardId then
                return stack, entry.guid
            end
        end
        return nil, nil
    end

    if isCard(stack) and stack.getGMNotes() == cardId then
        return stack, nil
    end

    return nil, nil
end

local function formatTags(tags)
    if not tags or tags == "" then
        return nil
    end
    return tags:gsub(";", ", ")
end

local function sanitizeText(text)
    if not text or text == "" then
        return nil
    end
    return text:gsub("<br%s*/?>", "\n")
end

local function buildCardDescription(card)
    local lines = {}
    local stats = {}

    if card.type == "Mission" and card.tier then
        table.insert(lines, card.tier .. " Mission")
    elseif card.type == "Tech" then
        table.insert(lines, "Technology")
    elseif card.type == "Event" then
        table.insert(lines, "Round Event")
    end

    if card.cost and card.type ~= "Mission" and card.type ~= "Event" then
        table.insert(stats, "Cost " .. tostring(card.cost))
    end
    if card.thrust then
        table.insert(stats, "Thrust " .. tostring(card.thrust))
    end
    if card.range then
        table.insert(stats, "Range " .. tostring(card.range))
    end
    if card.mass then
        table.insert(stats, "Mass " .. tostring(card.mass))
    end
    if card.reliability then
        table.insert(stats, "Reliability " .. tostring(card.reliability))
    end
    if card.energy then
        if card.energyMode == "Gen" then
            table.insert(stats, "Energy +" .. tostring(card.energy))
        elseif card.energyMode == "Storage" then
            table.insert(stats, "Storage " .. tostring(card.energy))
        else
            table.insert(stats, "Energy " .. tostring(card.energy))
        end
    end
    if card.vp and card.vp > 0 then
        table.insert(stats, "VP " .. tostring(card.vp))
    end
    if card.reward and card.reward > 0 then
        table.insert(stats, "Reward " .. tostring(card.reward) .. " Credits")
    end

    if #stats > 0 then
        table.insert(lines, table.concat(stats, " | "))
    end

    if card.isBasic then
        table.insert(lines, "Basic card")
    end

    local tags = formatTags(card.tags)
    if tags then
        table.insert(lines, "Tags: " .. tags)
    end

    local text = sanitizeText(card.text)
    if text then
        table.insert(lines, text)
    end

    return table.concat(lines, "\n")
end

local function cardFaceURL(cardIndex)
    return BASE_IMAGE_URL .. string.format("template_tts_%03d.png", cardIndex)
end

local function filterCards(predicate)
    local result = {}
    for _, card in ipairs(CARDS) do
        if predicate(card) then
            table.insert(result, card)
        end
    end
    table.sort(result, function(left, right)
        return left.card_index < right.card_index
    end)
    return result
end

local function expandCopies(cards)
    local expanded = {}
    for _, card in ipairs(cards) do
        for offset = 0, (card.copies or 1) - 1 do
            local copy = cloneTable(card)
            copy.card_index = card.card_index + offset
            copy.copyNumber = offset + 1
            table.insert(expanded, copy)
        end
    end

    table.sort(expanded, function(left, right)
        return left.card_index < right.card_index
    end)
    return expanded
end

local function buildDeckState(cards, deckName, layout)
    local physicalCards = expandCopies(cards)
    local customDeck = {}
    local deckIds = {}
    local contained = {}
    local baseColor = TYPE_COLOR[physicalCards[1].type] or { 0.72, 0.72, 0.72 }
    local rotZ = layout.faceDown and 180 or 0

    for _, card in ipairs(physicalCards) do
        local idx = card.card_index
        customDeck[tostring(idx)] = {
            FaceURL = cardFaceURL(idx),
            BackURL = CARD_BACK_URL,
            NumWidth = 1,
            NumHeight = 1,
            BackIsHidden = true,
            UniqueBack = false,
        }

        local cardId = idx * 100
        table.insert(deckIds, cardId)

        table.insert(contained, {
            Name = "Card",
            Nickname = card.name,
            Description = buildCardDescription(card),
            GMNotes = card.id,
            CardID = cardId,
            ColorDiffuse = { r = baseColor[1], g = baseColor[2], b = baseColor[3] },
            CustomDeck = { [tostring(idx)] = customDeck[tostring(idx)] },
            Transform = {
                posX = 0, posY = 0, posZ = 0,
                rotX = 0, rotY = 180, rotZ = 0,
                scaleX = 1, scaleY = 1, scaleZ = 1,
            },
            Locked = false,
            Grid = true,
            Snap = true,
            Autoraise = true,
            Sticky = true,
            Tooltip = true,
        })
    end

    return {
        Name = "DeckCustom",
        Nickname = deckName,
        Description = deckName,
        GMNotes = MANAGED_NOTE,
        ColorDiffuse = { r = baseColor[1], g = baseColor[2], b = baseColor[3] },
        Transform = {
            posX = layout.pos[1], posY = layout.pos[2], posZ = layout.pos[3],
            rotX = 0, rotY = layout.rotY or 0, rotZ = rotZ,
            scaleX = 1, scaleY = 1, scaleZ = 1,
        },
        Locked = false,
        Grid = true,
        Snap = true,
        Autoraise = true,
        Sticky = true,
        Tooltip = true,
        CustomDeck = customDeck,
        DeckIDs = deckIds,
        ContainedObjects = contained,
    }
end

local function spawnFromState(state)
    return spawnObjectJSON({ json = JSON.encode(state), sound = false, snap_to_grid = true })
end

local function applyBoardLayout(board, position, rotation)
    local bounds = board.getVisualBoundsNormalized() or board.getBoundsNormalized()
    if bounds and bounds.size and bounds.size.x > 0 and bounds.size.z > 0 then
        local currentScale = board.getScale()
        board.setScale({
            x = currentScale.x * BOARD_TARGET_SIZE.x / bounds.size.x,
            y = currentScale.y,
            z = currentScale.z * BOARD_TARGET_SIZE.z / bounds.size.z,
        })
    end

    board.setPosition(position)
    board.setRotation(rotation)
    board.setName("Orbital Map")
    board.setDescription("Space Agency Race board")
    board.setGMNotes(MANAGED_NOTE)
    board.setLock(true)
    board.use_grid = true
    board.auto_raise = true
    board.sticky = true
    board.tooltip = true
end

local function configureBoardObject(board, position, rotation)
    board.setCustomObject({ image = BOARD_IMAGE_URL })
    board = board.reload()

    Wait.condition(function()
        applyBoardLayout(board, position, rotation)
    end, function()
        return board ~= nil and not board.spawning and not board.loading_custom
    end)

    return board
end

local function spawnBoard()
    local position = { x = BOARD_POS.x, y = BOARD_POS.y, z = BOARD_POS.z }
    local rotation = { x = 0, y = 0, z = 0 }
    local board = spawnObject({
        type = "Custom_Board",
        position = position,
        rotation = rotation,
        scale = { x = 1, y = 1, z = 1 },
        sound = false,
        snap_to_grid = false,
    })

    return configureBoardObject(board, position, rotation)
end

local function ensureBoardPresent()
    local board = findObjectByName("Orbital Map")
    if board then
        return configureBoardObject(board, board.getPosition(), board.getRotation())
    end

    return spawnBoard()
end

local function trackerPosition(spec, value, playerColor)
    local tintIndex = 0
    for index, color in ipairs(PLAYER_ORDER) do
        if color == playerColor then
            tintIndex = index - 1
            break
        end
    end
    return { spec.x0 + spec.step * value, TRACKER_Y_BASE + tintIndex * TRACKER_Y_STEP, spec.z }
end

local function createTracker(kind, color, value)
    local spec = kind == "vp" and VP_TRACK or CREDIT_TRACK
    local tint = PLAYER_TINTS[color]
    local tracker = spawnObject({
        type = "Chip_10",
        position = trackerPosition(spec, value, color),
        scale = { 0.6, 0.6, 0.6 },
    })
    tracker.setName(spec.prefix .. color)
    tracker.setDescription(kind == "vp" and "Victory points" or "Credits")
    tracker.setColorTint(tint)
    tracker.setLock(false)
    markManaged(tracker)
    return tracker
end

local function createAgencyMarker(color)
    local tint = PLAYER_TINTS[color]
    local marker = spawnObject({
        type = "Chip_10",
        position = AGENCY_LEVEL_TRACKS[color][1],
        scale = { 0.55, 0.55, 0.55 },
    })
    marker.setName("Agency Marker - " .. color)
    marker.setDescription(color .. " agency level")
    marker.setColorTint(tint)
    marker.setLock(false)
    markManaged(marker)
    return marker
end

local function createReliabilityDie(color, position)
    local die = spawnObject({ type = "Die_10", position = position })
    die.setName("Reliability Die - " .. color)
    die.setColorTint(PLAYER_TINTS[color])
    markManaged(die)
    return die
end

local function createFirstPlayerToken()
    local token = spawnObject({
        type = "Chip_10",
        position = FIRST_PLAYER_POS,
        scale = { 0.7, 0.7, 0.7 },
    })
    token.setName("First Player")
    token.setDescription("Pass to the first player each round")
    token.setColorTint({ r = 0.97, g = 0.82, b = 0.19 })
    markManaged(token)
    return token
end

local function createCraftBag()
    local craftMarkers = {}
    for _, color in ipairs(PLAYER_ORDER) do
        local tint = PLAYER_TINTS[color]
        for copy = 1, 6 do
            table.insert(craftMarkers, {
                Name = "Custom_Model",
                Nickname = "Craft Marker - " .. color,
                Description = "Craft marker " .. tostring(copy) .. " for " .. color,
                GMNotes = MANAGED_NOTE,
                ColorDiffuse = { r = tint.r, g = tint.g, b = tint.b },
                Transform = {
                    posX = 0, posY = 0, posZ = 0,
                    rotX = 0, rotY = 0, rotZ = 0,
                    scaleX = 0.04, scaleY = 0.04, scaleZ = 0.04,
                },
                CustomMesh = {
                    MeshURL = SHIP_MODEL_URL,
                    DiffuseURL = "",
                    NormalURL = "",
                    ColliderURL = "",
                    Convex = true,
                    MaterialIndex = 0,
                    TypeIndex = 0,
                    CastShadows = true,
                },
                Locked = false,
                Grid = true,
                Snap = true,
                Autoraise = true,
                Sticky = true,
                Tooltip = true,
            })
        end
    end

    local bag = spawnObjectJSON({
        json = JSON.encode({
            Name = "Bag",
            Nickname = "Craft Markers",
            Description = "Six craft markers per player",
            GMNotes = MANAGED_NOTE,
            ColorDiffuse = { r = 0.18, g = 0.18, b = 0.32 },
            Transform = {
                posX = CRAFT_BAG_POS[1], posY = CRAFT_BAG_POS[2], posZ = CRAFT_BAG_POS[3],
                rotX = 0, rotY = 0, rotZ = 0,
                scaleX = 1, scaleY = 1, scaleZ = 1,
            },
            Locked = false,
            Grid = true,
            Snap = true,
            Autoraise = true,
            Sticky = true,
            Tooltip = true,
            ContainedObjects = craftMarkers,
        }),
        sound = false,
        snap_to_grid = true,
    })
    return markManaged(bag)
end

local function ensurePanelObject(name, position)
    local panel = findObjectByName(name)
    if panel and getTag(panel) ~= "BlockRectangle" then
        panel.destruct()
        panel = nil
    end
    if not panel then
        panel = spawnObject({
            type = "BlockRectangle",
            position = position,
            scale = { 1, 1, 1 },
            sound = false,
            snap_to_grid = false,
        })
    end
    return panel
end

local function applyTextPanelStyle(panel, name, labelText, descriptionText, position, style)
    panel.setName(name)
    panel.setDescription(descriptionText or labelText)
    panel.setPosition(position)
    panel.setRotation({ 0, 0, 0 })
    panel.setScale(style.scale)
    panel.setColorTint(style.tileColor)
    panel.setLock(true)
    markManaged(panel)
    panel.clearButtons()
    panel.createButton({
        click_function = "onLayoutPanelClicked",
        function_owner = Global,
        label = labelText,
        tooltip = descriptionText or labelText,
        position = { 0, 0.28, 0 },
        rotation = { 0, 0, 0 },
        width = style.width,
        height = style.height,
        font_size = style.fontSize,
        font_color = style.fontColor,
        color = style.buttonColor or style.tileColor,
        hover_color = style.hoverColor or style.buttonColor or style.tileColor,
        press_color = style.pressColor or style.buttonColor or style.tileColor,
    })
    return panel
end

local function ensureTextPanel(name, labelText, descriptionText, position, styleKey)
    local panel = ensurePanelObject(name, position)
    local style = PANEL_STYLES[styleKey or "default"] or PANEL_STYLES.default
    return applyTextPanelStyle(panel, name, labelText, descriptionText, position, style)
end

local function createLabel(name, text, position, styleKey)
    return ensureTextPanel(name, text, text, position, styleKey)
end

local function ensureAgencyTrackLabels()
end

local function removeObsoleteLabels()
    for _, name in ipairs(OBSOLETE_LABEL_NAMES) do
        local obj = findObjectByName(name)
        if obj then
            obj.destruct()
        end
    end
end

local function ensureCardLabels()
    for _, label in ipairs(CARD_LABELS) do
        createLabel(label.name, label.text, label.pos, label.style)
    end
end

local function controlButtonPosition(index)
    local zeroBasedIndex = index - 1
    local column = zeroBasedIndex % CONTROL_LAYOUT.columns
    local row = math.floor(zeroBasedIndex / CONTROL_LAYOUT.columns)
    return {
        CONTROL_LAYOUT.origin[1] + column * CONTROL_LAYOUT.columnStep,
        CONTROL_LAYOUT.origin[2],
        CONTROL_LAYOUT.origin[3] - row * CONTROL_LAYOUT.rowStep,
    }
end

local function applyControlTileStyle(tile, name, label, callback, position)
    tile.setName(name)
    tile.setPosition(position)
    tile.setScale(CONTROL_LAYOUT.tileScale)
    tile.setColorTint({ 0.10, 0.10, 0.28 })
    tile.setLock(true)
    markManaged(tile)
    tile.clearButtons()
    tile.createButton({
        click_function = callback,
        function_owner = Global,
        label = label,
        tooltip = name,
        position = { 0, 0.50, 0 },
        rotation = { 0, 0, 0 },
        width = CONTROL_LAYOUT.buttonWidth,
        height = CONTROL_LAYOUT.buttonHeight,
        font_size = CONTROL_LAYOUT.fontSize,
        color = { 0.92, 0.92, 0.96 },
        hover_color = { 1.00, 1.00, 1.00 },
        press_color = { 0.58, 0.78, 1.00 },
    })
end

local function createControlTile(name, label, callback, position)
    local tile = spawnObject({
        type = "BlockRectangle",
        position = position,
        scale = CONTROL_LAYOUT.tileScale,
    })
    applyControlTileStyle(tile, name, label, callback, position)
    return tile
end

local function ensureControlTiles()
    for index, control in ipairs(CONTROL_BUTTONS) do
        local position = controlButtonPosition(index)
        local tile = findObjectByName(control.name)
        if not tile then
            createControlTile(control.name, control.label, control.callback, position)
        else
            applyControlTileStyle(tile, control.name, control.label, control.callback, position)
        end
    end
end

local function ensureRulebookNote()
    return ensureTextPanel(
        "Rulebook Reference",
        "Rulebook\nHover for URL",
        "Rulebook URL\n" .. RULEBOOK_URL,
        RULEBOOK_NOTE_POS,
        "info"
    )
end

local function effectiveTransferWindow()
    local modifier = 0
    if gameState.activeEventId == "EV06" then
        modifier = 2
    elseif gameState.activeEventId == "EV07" then
        modifier = -2
    end
    return clamp(gameState.transferWindowBase + modifier, 0, 5)
end

local function currentHandLimit()
    if gameState.activeEventId == "EV08" then
        return 7
    end
    return 5
end

local function getParticipatingColors(fallbackColor)
    local colors = {}
    for _, player in ipairs(Player.getPlayers()) do
        if player.seated and isPlayableColor(player.color) then
            table.insert(colors, player.color)
        end
    end
    if #colors == 0 and fallbackColor and isPlayableColor(fallbackColor) then
        table.insert(colors, fallbackColor)
    end
    return colors
end

local function findTrackerObject(kind, playerColor)
    local prefix = kind == "vp" and VP_TRACK.prefix or CREDIT_TRACK.prefix
    return findObjectByName(prefix .. playerColor)
end

local function trackerSpec(kind)
    return kind == "vp" and VP_TRACK or CREDIT_TRACK
end

local function getTrackerValue(kind, playerColor)
    local obj = findTrackerObject(kind, playerColor)
    if not obj then
        return nil
    end
    local spec = trackerSpec(kind)
    local pos = obj.getPosition()
    local raw = (pos.x - spec.x0) / spec.step
    return clamp(math.floor(raw + 0.5), 0, spec.max)
end

local function setTrackerValue(kind, playerColor, value)
    local obj = findTrackerObject(kind, playerColor)
    if not obj then
        return false
    end
    local spec = trackerSpec(kind)
    local clamped = clamp(value, 0, spec.max)
    obj.setPositionSmooth(trackerPosition(spec, clamped, playerColor), false, true)
    return true
end

local function addTrackerValue(kind, playerColor, delta)
    local current = getTrackerValue(kind, playerColor)
    if current == nil then
        return false
    end
    return setTrackerValue(kind, playerColor, current + delta)
end

local function findAgencyMarker(playerColor)
    return findObjectByName("Agency Marker - " .. playerColor)
end

local function updateAgencyMarkers()
    for _, color in ipairs(PLAYER_ORDER) do
        local marker = findAgencyMarker(color)
        local level = clamp(gameState.agencyLevels[color] or 1, 1, 3)
        if marker then
            marker.setPositionSmooth(AGENCY_LEVEL_TRACKS[color][level], false, true)
            marker.setDescription("Agency level " .. tostring(level) .. "\nCommand turns: " .. tostring(level))
        end
    end
end

local function ensureRoundStatusNote()
    local tier2Text = gameState.unlockedTier2 and "Unlocked" or "Locked"
    local tier3Text = gameState.unlockedTier3 and "Unlocked" or "Locked"
    local tw = effectiveTransferWindow()
    local eventLine = gameState.activeEventName or "None"

    local statusText = table.concat({
        "Round " .. tostring(gameState.currentRound),
        "TW " .. tostring(tw) .. " (base " .. tostring(gameState.transferWindowBase) .. ")",
        "Event: " .. eventLine,
        "Hand limit: " .. tostring(currentHandLimit()),
        "Tier 2: " .. tier2Text,
        "Tier 3: " .. tier3Text,
        "Basics always available:",
        "Sterling Booster, Standard Tank, Heat Shield",
    }, "\n")

    return ensureTextPanel("Round Status", statusText, statusText, ROUND_STATUS_POS, "status")
end

local function updateTransferWindowMarker()
    local marker = findObjectByName("Transfer Window Marker")
    if not marker then
        return
    end
    local value = effectiveTransferWindow()
    local pos = TRANSFER_WINDOW_POSITIONS[value + 1]
    marker.setPositionSmooth(pos, false, true)
    marker.setDescription("Current transfer window cost: " .. tostring(value))
end

local function updateVisualState()
    updateTransferWindowMarker()
    updateAgencyMarkers()
    ensureRoundStatusNote()
    ensureRulebookNote()
end

local function cleanupTable()
    for _, obj in ipairs(getAllObjects()) do
        local name = obj.getName() or ""
        local gmNotes = getManagedNote(obj)
        if (not obj.getLock()) or gmNotes == MANAGED_NOTE or isLegacyManagedName(name) then
            obj.destruct()
        end
    end
end

local function slotOccupied(position, radius)
    local radiusSq = radius * radius
    for _, obj in ipairs(getAllObjects()) do
        if isCardStack(obj) then
            local pos = obj.getPosition()
            if distanceSquared(pos, position) <= radiusSq then
                return true
            end
        end
    end
    return false
end

local function findCardAt(position, radius)
    local radiusSq = radius * radius
    for _, obj in ipairs(getAllObjects()) do
        if isCard(obj) then
            local pos = obj.getPosition()
            if distanceSquared(pos, position) <= radiusSq then
                return obj
            end
        end
    end
    return nil
end

local function planarDistanceSquared(pos, target)
    local dx = pos.x - target[1]
    local dz = pos.z - target[3]
    return dx * dx + dz * dz
end

local function isNearAnyAnchor(pos, anchors, radius)
    local radiusSq = radius * radius
    for _, anchor in ipairs(anchors) do
        if planarDistanceSquared(pos, anchor) <= radiusSq then
            return true
        end
    end
    return false
end

local function isNearDisplaySlot(pos, slots, radius)
    return isNearAnyAnchor(pos, slots, radius)
end

local function cardDefinitionForObject(obj)
    if isCard(obj) then
        return CARD_BY_ID[obj.getGMNotes()]
    end
    if isDeck(obj) then
        local objects = obj.getObjects()
        if objects and objects[1] then
            return CARD_BY_ID[objects[1].gm_notes]
        end
    end
    return nil
end

local function recoveryLayoutForCard(cardDef)
    if not cardDef then
        return nil
    end
    if cardDef.type == "Event" then
        return SUPPLY_RECOVERY_LAYOUTS.event
    end
    if cardDef.type == "Mission" then
        if cardDef.tier == "Tier 3" and not gameState.unlockedTier3 then
            return SUPPLY_RECOVERY_LAYOUTS.tier3
        end
        if cardDef.tier == "Tier 2" and not gameState.unlockedTier2 then
            return SUPPLY_RECOVERY_LAYOUTS.tier2
        end
        return SUPPLY_RECOVERY_LAYOUTS.mission
    end
    return SUPPLY_RECOVERY_LAYOUTS.component
end

local function recoverLegacySupplyCards()
    for _, obj in ipairs(getAllObjects()) do
        if isCardStack(obj) then
            local cardDef = cardDefinitionForObject(obj)
            local layout = recoveryLayoutForCard(cardDef)
            local pos = obj.getPosition()
            local shouldRecover = layout and isNearAnyAnchor(pos, layout.anchors, 2.6)
            if not shouldRecover and cardDef and cardDef.type == "Mission" then
                shouldRecover = not isNearDisplaySlot(pos, MISSION_DISPLAY_POSITIONS, 2.0) and pos.x > 11.4
            end
            if shouldRecover and layout then
                obj.setPositionSmooth(layout.target, false, true)
                obj.setRotationSmooth(layout.rotation, false, true)
            end
        end
    end
end

local function repositionNamedObject(name, position, rotation)
    local obj = findObjectByName(name)
    if obj then
        obj.setPositionSmooth(position, false, true)
        if rotation then
            obj.setRotationSmooth(rotation, false, true)
        end
    end
end

local function syncLooseCardsAtPositions(slots)
    for _, slot in ipairs(slots) do
        local card = findCardAt(slot, 1.5)
        if card then
            card.setPositionSmooth(slot, false, true)
        end
    end
end

local function syncManagedLayoutObjects()
    removeObsoleteLabels()
    repositionNamedObject(DECK_LAYOUT.component.name, DECK_LAYOUT.component.pos, { 0, DECK_LAYOUT.component.rotY, 180 })
    repositionNamedObject(DECK_LAYOUT.event.name, DECK_LAYOUT.event.pos, { 0, DECK_LAYOUT.event.rotY, 180 })
    repositionNamedObject(DECK_LAYOUT.mission.name, DECK_LAYOUT.mission.pos, { 0, DECK_LAYOUT.mission.rotY, 180 })
    repositionNamedObject(DECK_LAYOUT.tier2.name, DECK_LAYOUT.tier2.pos, { 0, DECK_LAYOUT.tier2.rotY, 180 })
    repositionNamedObject(DECK_LAYOUT.tier3.name, DECK_LAYOUT.tier3.pos, { 0, DECK_LAYOUT.tier3.rotY, 180 })
    repositionNamedObject("Craft Markers", CRAFT_BAG_POS)
    repositionNamedObject("First Player", FIRST_PLAYER_POS)
    recoverLegacySupplyCards()
    syncLooseCardsAtPositions(MARKET_POSITIONS)
    syncLooseCardsAtPositions(MISSION_DISPLAY_POSITIONS)
    syncLooseCardsAtPositions({ EVENT_DISPLAY_POS })
end

local function takeTopCardToPosition(sourcePos, targetPos, flip, rotation)
    local source = findCardStackNear(sourcePos, 2.2)
    if not source then
        return nil
    end

    if isDeck(source) then
        return source.takeObject({
            position = targetPos,
            rotation = rotation or { 0, 180, 0 },
            flip = flip == true,
            smooth = true,
        })
    end

    if isCard(source) then
        source.setPositionSmooth(targetPos, false, true)
        if rotation then
            source.setRotationSmooth(rotation, false, true)
        end
        if flip then
            source.flip()
        end
        return source
    end

    return nil
end

local function refillFaceUpSlots(sourcePos, slots)
    local delay = 0
    for _, slot in ipairs(slots) do
        if not slotOccupied(slot, 1.2) then
            Wait.time(function()
                takeTopCardToPosition(sourcePos, slot, true, { 0, 180, 0 })
            end, delay)
            delay = delay + 0.25
        end
    end
end

local function refillMarket()
    refillFaceUpSlots(DECK_LAYOUT.component.pos, MARKET_POSITIONS)
end

local function refillMissionDisplay()
    refillFaceUpSlots(DECK_LAYOUT.mission.pos, MISSION_DISPLAY_POSITIONS)
end

local function findSupplyCard(cardId)
    local deck, guid = findCardInDeckById(cardId, DECK_LAYOUT.component.pos)
    if deck then
        return deck, guid
    end
    local loose = findLooseCardByIdNearPositions(cardId, MARKET_POSITIONS, 1.3)
    if loose then
        return loose, nil
    end
    return nil, nil
end

local function takeSupplyCardToHand(cardId, playerColor)
    local container, guid = findSupplyCard(cardId)
    if not container then
        return false
    end

    local taken = nil
    if guid then
        taken = container.takeObject({ guid = guid, smooth = false })
    else
        taken = container
    end

    if taken and taken.deal then
        taken.deal(1, playerColor)
        return true
    end

    return false
end

local function drawCardsToSeatedPlayers(count)
    local colors = getParticipatingColors(nil)
    if #colors == 0 then
        return
    end

    local delay = 0
    for _, color in ipairs(colors) do
        Wait.time(function()
            local stack = findCardStackNear(DECK_LAYOUT.component.pos, 2.2)
            if stack and stack.deal then
                stack.deal(count, color)
            end
        end, delay)
        delay = delay + 0.35
    end
end

local function dealStartingHands()
    local colors = getParticipatingColors(nil)
    if #colors == 0 then
        broadcastToAll("No seated players found. Sit at a player seat first.", "Red")
        return
    end

    local delay = 0
    for _, color in ipairs(colors) do
        for _, cardId in ipairs(STARTING_CARDS) do
            Wait.time(function()
                if not takeSupplyCardToHand(cardId, color) then
                    broadcastToAll("Could not find starting card " .. cardId .. " in the component supply.", "Red")
                end
            end, delay)
            delay = delay + 0.20
        end
    end

    Wait.time(function()
        refillMarket()
        broadcastToAll("Starting hands dealt: Sterling Booster, Standard Tank, and Heat Shield.", "Yellow")
    end, delay + 0.6)
end

local function discoverActiveEventFromTable()
    local card = findCardAt(EVENT_DISPLAY_POS, 1.5)
    if not card then
        gameState.activeEventId = nil
        gameState.activeEventName = nil
        return
    end

    local cardId = card.getGMNotes()
    local eventDef = CARD_BY_ID[cardId]
    gameState.activeEventId = cardId
    gameState.activeEventName = eventDef and eventDef.name or cardId
end

local function applyCatchUpGrant(amount, triggerColor)
    local colors = getParticipatingColors(triggerColor)
    if #colors == 0 then
        return
    end

    local lowestVp = nil
    local vpTied = {}
    for _, color in ipairs(colors) do
        local vp = getTrackerValue("vp", color) or 0
        if lowestVp == nil or vp < lowestVp then
            lowestVp = vp
            vpTied = { color }
        elseif vp == lowestVp then
            table.insert(vpTied, color)
        end
    end

    if #vpTied == 1 then
        addTrackerValue("credit", vpTied[1], amount)
        broadcastToAll(vpTied[1] .. " gains the catch-up grant: +" .. tostring(amount) .. " Credits.", "Yellow")
        return
    end

    local lowestCredit = nil
    local creditTied = {}
    for _, color in ipairs(vpTied) do
        local credit = getTrackerValue("credit", color) or 0
        if lowestCredit == nil or credit < lowestCredit then
            lowestCredit = credit
            creditTied = { color }
        elseif credit == lowestCredit then
            table.insert(creditTied, color)
        end
    end

    if #creditTied == 1 then
        addTrackerValue("credit", creditTied[1], amount)
        broadcastToAll(creditTied[1] .. " wins the catch-up tiebreak and gains +" .. tostring(amount) .. " Credits.", "Yellow")
        return
    end

    for _, color in ipairs(creditTied) do
        addTrackerValue("credit", color, 2)
    end
    broadcastToAll("Catch-up grant tie: each tied player gains 2 Credits.", "Yellow")
end

local function mergeDeckStacks(targetPos, sourcePos, targetName, targetRotY)
    local target = findCardStackNear(targetPos, 2.2)
    local source = findCardStackNear(sourcePos, 2.2)
    if not source then
        return
    end

    if not target then
        source.setPositionSmooth(targetPos, false, true)
        source.setRotationSmooth({ 0, targetRotY, 180 }, false, true)
        source.setName(targetName)
        if source.setGMNotes then
            source.setGMNotes(MANAGED_NOTE)
        end
        Wait.time(function()
            local stack = findCardStackNear(targetPos, 2.2)
            if stack and stack.shuffle then
                stack.shuffle()
            end
        end, 0.8)
        return
    end

    if isDeck(target) then
        target.putObject(source)
        Wait.time(function()
            local stack = findCardStackNear(targetPos, 2.2)
            if stack then
                stack.setName(targetName)
                if stack.setGMNotes then
                    stack.setGMNotes(MANAGED_NOTE)
                end
                if stack.shuffle then
                    stack.shuffle()
                end
            end
        end, 0.8)
        return
    end

    if isDeck(source) then
        source.putObject(target)
        source.setPositionSmooth(targetPos, false, true)
        source.setRotationSmooth({ 0, targetRotY, 180 }, false, true)
        source.setName(targetName)
        if source.setGMNotes then
            source.setGMNotes(MANAGED_NOTE)
        end
        Wait.time(function()
            local stack = findCardStackNear(targetPos, 2.2)
            if stack and stack.shuffle then
                stack.shuffle()
            end
        end, 0.8)
        return
    end

    source.setPositionSmooth(targetPos, false, true)
    Wait.time(function()
        local stack = findCardStackNear(targetPos, 2.2)
        if stack then
            stack.setName(targetName)
            if stack.setGMNotes then
                stack.setGMNotes(MANAGED_NOTE)
            end
            if stack.shuffle then
                stack.shuffle()
            end
        end
    end, 0.8)
end

local function unlockMissionTier(tier, triggerColor)
    if tier == 2 then
        if gameState.unlockedTier2 then
            return
        end
        gameState.unlockedTier2 = true
        mergeDeckStacks(DECK_LAYOUT.mission.pos, DECK_LAYOUT.tier2.pos, DECK_LAYOUT.mission.name, DECK_LAYOUT.mission.rotY)
        applyCatchUpGrant(3, triggerColor)
        broadcastToAll("Tier 2 missions are now shuffled into the mission deck.", "Yellow")
    elseif tier == 3 then
        if gameState.unlockedTier3 then
            return
        end
        gameState.unlockedTier3 = true
        mergeDeckStacks(DECK_LAYOUT.mission.pos, DECK_LAYOUT.tier3.pos, DECK_LAYOUT.mission.name, DECK_LAYOUT.mission.rotY)
        applyCatchUpGrant(4, triggerColor)
        broadcastToAll("Tier 3 missions are now shuffled into the mission deck.", "Yellow")
    end

    Wait.time(updateVisualState, 1.0)
end

local function currentLevelCost(level)
    return LEVEL_COSTS[level] or 0
end

local function levelUpAgency(playerColor)
    if not isPlayableColor(playerColor) then
        broadcastToAll("Choose a player color before using Level Up.", "Red")
        return
    end

    local currentLevel = gameState.agencyLevels[playerColor] or 1
    if currentLevel >= 3 then
        broadcastToAll(playerColor .. " is already at Agency Level 3.", "Yellow")
        return
    end

    local nextLevel = currentLevel + 1
    local cost = currentLevelCost(nextLevel)
    local credits = getTrackerValue("credit", playerColor) or 0
    if credits < cost then
        broadcastToAll(playerColor .. " needs " .. tostring(cost) .. " Credits to reach Level " .. tostring(nextLevel) .. ".", "Red")
        return
    end

    setTrackerValue("credit", playerColor, credits - cost)
    gameState.agencyLevels[playerColor] = nextLevel

    if nextLevel == 2 and not gameState.unlockedTier2 then
        unlockMissionTier(2, playerColor)
    elseif nextLevel == 3 and not gameState.unlockedTier3 then
        addTrackerValue("vp", playerColor, 2)
        unlockMissionTier(3, playerColor)
    end

    updateVisualState()
    broadcastToAll(playerColor .. " reaches Agency Level " .. tostring(nextLevel) .. ".", "Yellow")
end

local function buyBasicCard(playerColor, cardId, cost)
    if not isPlayableColor(playerColor) then
        broadcastToAll("Choose a player color before buying a basic card.", "Red")
        return
    end

    local credits = getTrackerValue("credit", playerColor) or 0
    if credits < cost then
        broadcastToAll(playerColor .. " needs " .. tostring(cost) .. " Credits to buy " .. cardId .. ".", "Red")
        return
    end

    if not takeSupplyCardToHand(cardId, playerColor) then
        broadcastToAll("No copy of " .. cardId .. " is currently available in the component supply.", "Red")
        return
    end

    setTrackerValue("credit", playerColor, credits - cost)
    Wait.time(refillMarket, 0.6)
    updateVisualState()
end

local function applyEventImmediateEffects(eventId)
    local colors = getParticipatingColors(nil)
    if eventId == "EV02" then
        for _, color in ipairs(colors) do
            addTrackerValue("credit", color, 3)
        end
        broadcastToAll("Funding Boost: all seated players gain 3 Credits.", "Yellow")
    elseif eventId == "EV01" then
        broadcastToAll("Solar Storm: launches suffer -2 Reliability this round.", "Yellow")
    elseif eventId == "EV03" then
        broadcastToAll("Supply Delay: launches cost +1 Credit to prepare this round.", "Yellow")
    elseif eventId == "EV04" then
        broadcastToAll("Tech Breakthrough: the first player to launch this round may search the component deck for a Tech.", "Yellow")
    elseif eventId == "EV05" then
        broadcastToAll("Docking Opportunity: Docking craft that dock with an on-orbit station in High Orbit (GEO) gain +2 VP this round.", "Yellow")
    elseif eventId == "EV06" then
        broadcastToAll("Transfer Window Storm: TW cost is +2 this round (max 5).", "Yellow")
    elseif eventId == "EV07" then
        broadcastToAll("Launch Window: TW cost is -2 this round (min 0).", "Yellow")
    elseif eventId == "EV08" then
        broadcastToAll("Expanded Operations: hand limit is 7 this round.", "Yellow")
    end
end

local function revealEventCard()
    local existing = findCardAt(EVENT_DISPLAY_POS, 1.5)
    if existing then
        return false
    end

    local drawn = takeTopCardToPosition(DECK_LAYOUT.event.pos, EVENT_DISPLAY_POS, true, { 0, 180, 0 })
    if not drawn then
        gameState.activeEventId = nil
        gameState.activeEventName = nil
        return false
    end

    Wait.time(function()
        discoverActiveEventFromTable()
        if gameState.activeEventId then
            applyEventImmediateEffects(gameState.activeEventId)
        end
        updateVisualState()
    end, 0.8)

    return true
end

local function discardActiveEvent()
    local card = findCardAt(EVENT_DISPLAY_POS, 1.5)
    if card then
        card.destruct()
    end
    gameState.activeEventId = nil
    gameState.activeEventName = nil
end

local function planningPhase()
    if gameState.activeEventId then
        broadcastToAll("An event is already active. Resolve Maintenance before starting the next Planning phase.", "Red")
        return
    end

    gameState.currentRound = gameState.currentRound + 1
    gameState.transferWindowBase = (gameState.transferWindowBase + 1) % 6
    revealEventCard()
    drawCardsToSeatedPlayers(2)

    Wait.time(function()
        updateVisualState()
        broadcastToAll(
            "Planning Phase: event revealed, TW is now " .. tostring(effectiveTransferWindow()) .. ", and each seated player draws 2 cards. Emergency sell remains manual.",
            "Yellow"
        )
    end, 1.0)
end

local function maintenancePhase()
    local hadEvent = gameState.activeEventName
    discardActiveEvent()

    Wait.time(function()
        refillMissionDisplay()
        refillMarket()
    end, 0.2)

    Wait.time(function()
        updateVisualState()
        local eventText = hadEvent and ("Event discarded: " .. hadEvent .. ". ") or ""
        broadcastToAll(eventText .. "Maintenance: reusable recovery, battery storage, mission refill, and market refill are ready.", "Yellow")
    end, 1.0)
end

local function spawnCoreDecks()
    local componentCards = filterCards(function(card)
        return card.type ~= "Mission" and card.type ~= "Event"
    end)
    local eventCards = filterCards(function(card)
        return card.type == "Event"
    end)
    local tier1Missions = filterCards(function(card)
        return card.type == "Mission" and card.tier == "Tier 1"
    end)
    local tier2Missions = filterCards(function(card)
        return card.type == "Mission" and card.tier == "Tier 2"
    end)
    local tier3Missions = filterCards(function(card)
        return card.type == "Mission" and card.tier == "Tier 3"
    end)

    spawnFromState(buildDeckState(componentCards, DECK_LAYOUT.component.name, DECK_LAYOUT.component))
    spawnFromState(buildDeckState(eventCards, DECK_LAYOUT.event.name, DECK_LAYOUT.event))
    spawnFromState(buildDeckState(tier1Missions, DECK_LAYOUT.mission.name, DECK_LAYOUT.mission))
    spawnFromState(buildDeckState(tier2Missions, DECK_LAYOUT.tier2.name, DECK_LAYOUT.tier2))
    spawnFromState(buildDeckState(tier3Missions, DECK_LAYOUT.tier3.name, DECK_LAYOUT.tier3))
end

local function shuffleStackAt(position)
    local stack = findCardStackNear(position, 2.2)
    if stack and stack.shuffle then
        stack.shuffle()
    end
end

local function setupGame()
    cleanupTable()
    resetState()

    Wait.time(function()
        spawnBoard()
        spawnCoreDecks()

        for _, color in ipairs(PLAYER_ORDER) do
            createTracker("vp", color, 0)
            createTracker("credit", color, CREDIT_TRACK.start)
            createAgencyMarker(color)
        end

        ensureAgencyTrackLabels()

        for _, dieSpec in ipairs(RELIABILITY_DICE) do
            createReliabilityDie(dieSpec.color, dieSpec.pos)
        end

        createFirstPlayerToken()
        createCraftBag()

        local marker = spawnObject({
            type = "Chip_10",
            position = TRANSFER_WINDOW_POSITIONS[1],
            scale = { 0.65, 0.65, 0.65 },
        })
        marker.setName("Transfer Window Marker")
        marker.setColorTint({ r = 0.96, g = 0.38, b = 0.60 })
        marker.setLock(false)
        markManaged(marker)

        ensureCardLabels()

        ensureControlTiles()
        ensureRulebookNote()
        ensureRoundStatusNote()

        Wait.time(function()
            shuffleStackAt(DECK_LAYOUT.component.pos)
            shuffleStackAt(DECK_LAYOUT.event.pos)
            shuffleStackAt(DECK_LAYOUT.mission.pos)
            shuffleStackAt(DECK_LAYOUT.tier2.pos)
            shuffleStackAt(DECK_LAYOUT.tier3.pos)
        end, 0.6)

        Wait.time(function()
            refillMarket()
            refillMissionDisplay()
            updateVisualState()
            broadcastToAll("Space Agency Race is set up. Seat players, deal starting hands, then begin the Planning phase.", "Yellow")
        end, 1.2)
    end, 0.3)
end

function onSave()
    return JSON.encode(normalizeState(gameState))
end

function onResetClicked(_, _, alt_click)
    if alt_click then
        return
    end
    setupGame()
end

function onDealHandsClicked(_, _, alt_click)
    if alt_click then
        return
    end
    dealStartingHands()
end

function onPlanningPhaseClicked(_, _, alt_click)
    if alt_click then
        return
    end
    planningPhase()
end

function onMaintenanceClicked(_, _, alt_click)
    if alt_click then
        return
    end
    maintenancePhase()
end

function onRefillMarketClicked(_, _, alt_click)
    if alt_click then
        return
    end
    refillMarket()
end

function onRefillMissionsClicked(_, _, alt_click)
    if alt_click then
        return
    end
    refillMissionDisplay()
end

function onLevelUpClicked(_, player_color, alt_click)
    if alt_click then
        return
    end
    levelUpAgency(player_color)
end

function onBuySterlingClicked(_, player_color, alt_click)
    if alt_click then
        return
    end
    buyBasicCard(player_color, "E02", 3)
end

function onBuyTankClicked(_, player_color, alt_click)
    if alt_click then
        return
    end
    buyBasicCard(player_color, "T01", 2)
end

function onBuyShieldClicked(_, player_color, alt_click)
    if alt_click then
        return
    end
    buyBasicCard(player_color, "S01", 1)
end

function onLayoutPanelClicked()
end

function onLoad(save_state)
    if save_state and save_state ~= "" then
        local ok, decoded = pcall(function()
            return JSON.decode(save_state)
        end)
        if ok and decoded then
            gameState = normalizeState(decoded)
        else
            resetState()
        end
    else
        resetState()
    end

    if save_state == nil or save_state == "" then
        Wait.time(setupGame, 0.8)
        return
    end

    Wait.time(function()
        ensureBoardPresent()
        syncManagedLayoutObjects()
        discoverActiveEventFromTable()
        ensureAgencyTrackLabels()
        ensureCardLabels()
        ensureControlTiles()
        ensureRulebookNote()
        ensureRoundStatusNote()
        updateVisualState()
    end, 0.8)
end