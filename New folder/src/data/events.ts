export type TicketTier = {
  id: string
  name: string
  price: number
  available: number
  description: string
}

export type Review = {
  author: string
  rating: number
  text: string
  date: string
}

export type Event = {
  id: string
  title: string
  category: string
  date: string
  time: string
  endTime: string
  venue: string
  location: string
  city: string
  country: string
  organizer: string
  description: string
  longDescription: string
  image: string
  gallery: string[]
  ticketTiers: TicketTier[]
  hasReservedSeating: boolean
  isFeatured: boolean
  isTrending: boolean
  rating: number
  reviewCount: number
  capacity: number
  tags: string[]
  reviews: Review[]
}

export type Booking = {
  id: string
  eventId: string
  tierId: string
  tierName: string
  quantity: number
  totalPrice: number
  purchasedAt: string
  seats?: string[]
  attendeeName: string
  attendeeEmail: string
}

const U = (id: string, w = 1200, h = 700) =>
  `https://images.unsplash.com/photo-${id}?w=${w}&h=${h}&fit=crop&auto=format`

export const CATEGORIES = [
  "All",
  "Concerts",
  "Festivals",
  "Conferences",
  "Sports",
  "Comedy",
  "Cultural",
  "Workshops",
  "Business",
  "Networking",
]

export const EVENTS: Event[] = [
  {
    id: "evt-001",
    title: "Neon Frequencies",
    category: "Festivals",
    date: "2026-09-12",
    time: "18:00",
    endTime: "06:00",
    venue: "Riverside Arena Park",
    location: "Austin, TX",
    city: "Austin",
    country: "USA",
    organizer: "Pulse Events Co.",
    description:
      "A three-day electronic music festival featuring world-class DJs across five stages.",
    longDescription:
      "Neon Frequencies returns for its fifth year, bigger and more electric than ever. Across three nights under the Texas stars, five curated stages will host over 60 artists spanning techno, house, ambient, and experimental electronic music.\n\nFrom veteran headliners to breakthrough acts, every set is a journey. The festival grounds include immersive art installations, premium food villages, and dedicated wellness zones for when the music needs a pause.\n\nGeneral camping passes are available. Early bird VIP packages include exclusive backstage access, premium viewing platforms, and a curated pre-party on Friday evening.",
    image: U("1470229722913-7c0e2dbbafd3"),
    gallery: [
      U("1506157786151-b8491531f063", 800, 500),
      U("1493225457124-a3eb161ffa5f", 800, 500),
      U("1429962714451-bb934ecdc4ec", 800, 500),
    ],
    ticketTiers: [
      { id: "t1", name: "General Admission", price: 149, available: 840, description: "3-day festival pass, general access to all stages" },
      { id: "t2", name: "VIP", price: 349, available: 120, description: "VIP lounges, premium viewing areas, backstage access" },
      { id: "t3", name: "Platinum", price: 699, available: 24, description: "All VIP perks + artist meet & greet + private bar" },
    ],
    hasReservedSeating: false,
    isFeatured: true,
    isTrending: true,
    rating: 4.9,
    reviewCount: 2847,
    capacity: 30000,
    tags: ["Electronic", "Outdoor", "Multi-day", "Camping"],
    reviews: [
      { author: "Maya Chen", rating: 5, text: "The best festival I've been to in a decade. The sound quality on Stage 2 was phenomenal.", date: "2025-09-15" },
      { author: "Jake Williams", rating: 5, text: "VIP is absolutely worth it. The backstage area had incredible artists walking around.", date: "2025-09-16" },
      { author: "Sofia Reyes", rating: 4, text: "Incredible lineup and production. The camping situation could use improvement.", date: "2025-09-14" },
    ],
  },
  {
    id: "evt-002",
    title: "Arctic Monkeys: The Comedown Machine Tour",
    category: "Concerts",
    date: "2026-09-27",
    time: "20:00",
    endTime: "23:30",
    venue: "Madison Square Garden",
    location: "New York, NY",
    city: "New York",
    country: "USA",
    organizer: "Live Nation Entertainment",
    description:
      "The legendary Sheffield rock band returns to MSG for one unforgettable night.",
    longDescription:
      "Arctic Monkeys bring their critically acclaimed Comedown Machine Tour to the iconic Madison Square Garden. Fresh off their most successful album cycle in a decade, the band will perform a career-spanning setlist across two hours of wall-to-wall rock.\n\nOpening the night is Wolf Alice, whose atmospheric indie rock is the perfect complement to the headliners. Doors open at 7PM with the first support act taking the stage at 7:30PM.\n\nReserved seating is available across all sections. Floor standing tickets are general admission. Early arrival is strongly recommended for floor ticket holders.",
    image: U("1501281668745-f7f57925c3b4"),
    gallery: [
      U("1514525253161-7a46d19cd819", 800, 500),
      U("1492684223066-81342ee5ff30", 800, 500),
    ],
    ticketTiers: [
      { id: "t1", name: "Floor – GA", price: 85, available: 320, description: "Standing floor, general admission" },
      { id: "t2", name: "Lower Bowl", price: 120, available: 180, description: "Reserved seating, lower level sections" },
      { id: "t3", name: "Upper Bowl", price: 65, available: 290, description: "Reserved seating, upper level sections" },
      { id: "t4", name: "VIP Package", price: 280, available: 40, description: "Premium floor + early access + merch bundle" },
    ],
    hasReservedSeating: true,
    isFeatured: true,
    isTrending: true,
    rating: 4.8,
    reviewCount: 1203,
    capacity: 20000,
    tags: ["Rock", "Indie", "Arena"],
    reviews: [
      { author: "Tom Baker", rating: 5, text: "They played every song I wanted. The setlist was perfect.", date: "2026-04-02" },
      { author: "Priya Mehta", rating: 5, text: "MSG is the perfect venue for them. Sound was incredible.", date: "2026-04-03" },
    ],
  },
  {
    id: "evt-003",
    title: "TechSummit 2026",
    category: "Conferences",
    date: "2026-10-08",
    time: "09:00",
    endTime: "18:00",
    venue: "Moscone Center",
    location: "San Francisco, CA",
    city: "San Francisco",
    country: "USA",
    organizer: "Summit Group Inc.",
    description:
      "Three days. 200+ speakers. The world's leading technology conference.",
    longDescription:
      "TechSummit 2026 is the premier technology conference of the year, bringing together founders, engineers, designers, and investors to explore the frontiers of AI, product design, and emerging platforms.\n\nWith over 200 speakers across 14 tracks, every attendee finds their niche. Keynotes from industry leaders, intimate workshop sessions, live product demos, and curated networking events fill three intensive days.\n\nAll passes include full conference access, morning workshops, evening receptions, and a comprehensive resource library. Enterprise passes add dedicated meeting rooms and executive roundtables.",
    image: U("1540575467063-178a50c2df87"),
    gallery: [
      U("1556761175-b413da4baf72", 800, 500),
      U("1524178232363-1fb2b075b655", 800, 500),
    ],
    ticketTiers: [
      { id: "t1", name: "Starter Pass", price: 299, available: 450, description: "Full conference access, 3 days" },
      { id: "t2", name: "Professional", price: 599, available: 200, description: "All access + hands-on workshop sessions" },
      { id: "t3", name: "Enterprise", price: 1299, available: 50, description: "All access + executive roundtables + private meeting rooms" },
    ],
    hasReservedSeating: false,
    isFeatured: true,
    isTrending: false,
    rating: 4.7,
    reviewCount: 892,
    capacity: 6000,
    tags: ["Technology", "Networking", "Professional"],
    reviews: [
      { author: "Alex Kim", rating: 5, text: "Best conference I've attended. The quality of speakers was exceptional.", date: "2025-10-10" },
      { author: "Rachel Torres", rating: 4, text: "Great content and connections. The app for scheduling sessions needs work.", date: "2025-10-09" },
    ],
  },
  {
    id: "evt-004",
    title: "Champions League Final 2026",
    category: "Sports",
    date: "2026-10-15",
    time: "20:45",
    endTime: "23:00",
    venue: "Wembley Stadium",
    location: "London, UK",
    city: "London",
    country: "UK",
    organizer: "UEFA Events",
    description:
      "The pinnacle of European club football. 90,000 fans. One trophy.",
    longDescription:
      "Wembley Stadium hosts the 2026 UEFA Champions League Final — the most watched club football event on the planet. With 90,000 seats and an electric atmosphere that transcends sport, this is a once-in-a-lifetime occasion.\n\nTickets are available across all price categories from the neutral fan allocation. Gates open 3 hours before kickoff.\n\nThe match will be broadcast in 200 countries. Arrive early and soak in the atmosphere of the world's greatest football stage.",
    image: U("1461896836934-ffe607ba8211"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "Category 3", price: 320, available: 180, description: "Behind the goal, upper tier" },
      { id: "t2", name: "Category 2", price: 520, available: 90, description: "Side stands, full pitch view" },
      { id: "t3", name: "Category 1", price: 890, available: 30, description: "Central premium seats, best sightlines" },
    ],
    hasReservedSeating: true,
    isFeatured: false,
    isTrending: true,
    rating: 4.9,
    reviewCount: 3421,
    capacity: 90000,
    tags: ["Football", "European", "Championship"],
    reviews: [
      { author: "Carlos Silva", rating: 5, text: "An experience I will never forget. Wembley under floodlights is something else.", date: "2025-10-16" },
    ],
  },
  {
    id: "evt-005",
    title: "Dave Chappelle: Midnight Return",
    category: "Comedy",
    date: "2026-09-19",
    time: "21:00",
    endTime: "23:00",
    venue: "The Comedy Cellar",
    location: "New York, NY",
    city: "New York",
    country: "USA",
    organizer: "Stand-Up Live Productions",
    description:
      "120 seats. No cameras. One of comedy's greatest performers in his natural habitat.",
    longDescription:
      "Dave Chappelle makes a rare return to the intimate club setting for a series of special midnight shows at The Comedy Cellar. With just 120 seats per show, this is as close as it gets to the raw, unfiltered genius of one of comedy's living legends.\n\nStrict phone policy enforced at the door using Yondr pouches. This is pure comedy in its natural habitat.\n\nDoors open at 10:30PM. Two-drink minimum per person. All sales final, no exchanges or refunds.",
    image: U("1507003211169-0a1dd7228f2d"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "General Admission", price: 65, available: 45, description: "Seating on first come basis + 2 drink minimum" },
      { id: "t2", name: "Premium Table", price: 140, available: 12, description: "Reserved front table for 2, bottle service included" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: true,
    rating: 4.9,
    reviewCount: 567,
    capacity: 120,
    tags: ["Stand-Up", "Intimate", "Late Night"],
    reviews: [
      { author: "Marcus Johnson", rating: 5, text: "Hands down the funniest two hours of my life. No phones was the right call.", date: "2026-03-20" },
    ],
  },
  {
    id: "evt-006",
    title: "Sakura Cultural Festival",
    category: "Cultural",
    date: "2026-09-26",
    time: "11:00",
    endTime: "22:00",
    venue: "Prospect Park",
    location: "Brooklyn, NY",
    city: "New York",
    country: "USA",
    organizer: "Japan Society NY",
    description:
      "A celebration of Japanese culture, food, art, and performance in the heart of Brooklyn.",
    longDescription:
      "The annual Sakura Cultural Festival transforms Prospect Park into a vibrant celebration of Japanese art, cuisine, performance, and community. Now in its 15th year, the festival draws over 25,000 visitors.\n\nHighlights include live taiko drumming, traditional tea ceremonies, contemporary J-pop performances, ikebana demonstrations, anime screenings, and a 200-vendor market.\n\nFestival entry is free. Premium Lantern Ceremony passes are limited for the Saturday evening highlight event.",
    image: U("1533174072545-7a4b6ad7a6c3"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "General Entry", price: 0, available: 5000, description: "Free daytime festival access" },
      { id: "t2", name: "Lantern Ceremony", price: 35, available: 200, description: "Evening lantern ceremony + sake tasting" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: false,
    rating: 4.6,
    reviewCount: 1089,
    capacity: 25000,
    tags: ["Japanese", "Family", "Outdoor", "Free"],
    reviews: [],
  },
  {
    id: "evt-007",
    title: "Jazz Under the Stars",
    category: "Concerts",
    date: "2026-09-05",
    time: "19:30",
    endTime: "23:00",
    venue: "Hollywood Bowl",
    location: "Los Angeles, CA",
    city: "Los Angeles",
    country: "USA",
    organizer: "LA Philharmonic",
    description:
      "Esperanza Spalding and Kamasi Washington share one legendary evening at the Hollywood Bowl.",
    longDescription:
      "The Hollywood Bowl's legendary summer jazz series reaches its grand finale with a double bill for the ages. Esperanza Spalding performs her full new album with a 22-piece ensemble before Kamasi Washington closes the evening with his signature cosmic jazz experience.\n\nBring a picnic, a blanket, and someone to share it with. Boxes and premium seats include table-service with full menu. Garden and bench sections are bring-your-own picnic.",
    image: U("1514525253161-7a46d19cd819"),
    gallery: [U("1429962714451-bb934ecdc4ec", 800, 500)],
    ticketTiers: [
      { id: "t1", name: "Bench", price: 35, available: 600, description: "Bench seating, upper sections" },
      { id: "t2", name: "Reserved Seat", price: 75, available: 280, description: "Reserved seat, mid-sections" },
      { id: "t3", name: "Box Seat", price: 165, available: 48, description: "Private box, full table service" },
    ],
    hasReservedSeating: true,
    isFeatured: false,
    isTrending: false,
    rating: 4.8,
    reviewCount: 743,
    capacity: 17500,
    tags: ["Jazz", "Outdoor", "Orchestra"],
    reviews: [],
  },
  {
    id: "evt-008",
    title: "Global Founders Summit",
    category: "Business",
    date: "2026-10-22",
    time: "08:30",
    endTime: "19:00",
    venue: "The Ritz Carlton",
    location: "Miami, FL",
    city: "Miami",
    country: "USA",
    organizer: "Founders Network",
    description:
      "500 founders, operators, and investors. One curated day of ideas and connections.",
    longDescription:
      "The Global Founders Summit is the most selective business event of the year — limited to 500 participants, curated by application, bringing together early-stage founders, Series A+ entrepreneurs, and the investors and operators who support them.\n\nFormatted as founder fireside conversations, investor office hours, working lunches, and evening cocktails, the summit maximizes meaningful connections over keynote theater.",
    image: U("1556761175-b413da4baf72"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "Founder Pass", price: 1800, available: 80, description: "Full day + dinner + investor introductions" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: false,
    rating: 4.7,
    reviewCount: 234,
    capacity: 500,
    tags: ["Startups", "Investors", "Exclusive"],
    reviews: [],
  },
  {
    id: "evt-009",
    title: "Design Systems Workshop",
    category: "Workshops",
    date: "2026-09-10",
    time: "10:00",
    endTime: "17:00",
    venue: "Studio 44",
    location: "San Francisco, CA",
    city: "San Francisco",
    country: "USA",
    organizer: "Design Systems Co.",
    description:
      "A full-day hands-on workshop on building scalable design systems, led by experts from Apple and Stripe.",
    longDescription:
      "Led by two veteran product designers with experience at Apple and Stripe, this workshop takes participants through the complete process of building and maintaining a world-class design system.\n\nMorning sessions cover component architecture and token strategy. Afternoon sessions are hands-on: participants build a complete design system using provided starter files.\n\nMaximum 24 participants. All workshop materials included.",
    image: U("1524178232363-1fb2b075b655"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "Workshop Seat", price: 395, available: 8, description: "Full day workshop, all materials included" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: false,
    rating: 4.9,
    reviewCount: 156,
    capacity: 24,
    tags: ["Design", "Professional Development"],
    reviews: [],
  },
  {
    id: "evt-010",
    title: "Midnight Techno: Warehouse Sessions",
    category: "Concerts",
    date: "2026-09-06",
    time: "23:00",
    endTime: "08:00",
    venue: "Unit 9 Warehouse",
    location: "Berlin, Germany",
    city: "Berlin",
    country: "Germany",
    organizer: "Ostgut Events",
    description:
      "Nine hours of relentless techno in a converted industrial warehouse — the authentic Berlin experience.",
    longDescription:
      "Berlin's most revered underground techno collective returns to Unit 9 Warehouse for an all-night session. Four rooms. Nine DJs. Lineup announced 48 hours before doors.\n\nThe industrial space has been upgraded with a new Funktion-One sound system. Cash bar only. Strictly no photography policy.",
    image: U("1492684223066-81342ee5ff30"),
    gallery: [U("1493225457124-a3eb161ffa5f", 800, 500)],
    ticketTiers: [
      { id: "t1", name: "Presale", price: 15, available: 80, description: "Discounted presale, guaranteed entry" },
      { id: "t2", name: "Door Ticket", price: 18, available: 220, description: "Standard entry, subject to queue" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: true,
    rating: 4.8,
    reviewCount: 892,
    capacity: 800,
    tags: ["Techno", "Underground", "All Night", "Berlin"],
    reviews: [],
  },
  {
    id: "evt-011",
    title: "Afrobeats & Afrofusion Night",
    category: "Concerts",
    date: "2026-09-20",
    time: "19:00",
    endTime: "02:00",
    venue: "O2 Academy Brixton",
    location: "London, UK",
    city: "London",
    country: "UK",
    organizer: "Afro Nation Events",
    description:
      "Burna Boy, Wizkid, and Tems headline an explosive evening celebrating the global rise of Afrobeats.",
    longDescription:
      "Three of the biggest names in Afrobeats share one stage for the first time — Burna Boy, Wizkid, and Tems — in what promises to be one of the most talked-about concerts of 2026.\n\nThe O2 Academy Brixton provides the perfect setting for a night of infectious rhythm, energy, and celebration. Support acts include emerging talents from Lagos, Accra, and London.",
    image: U("1429962714451-bb934ecdc4ec"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "Floor GA", price: 55, available: 280, description: "Standing floor, general admission" },
      { id: "t2", name: "Balcony Reserved", price: 75, available: 120, description: "Reserved balcony seating" },
      { id: "t3", name: "Box Package", price: 220, available: 16, description: "Private box for 4, hospitality included" },
    ],
    hasReservedSeating: true,
    isFeatured: true,
    isTrending: true,
    rating: 4.9,
    reviewCount: 1456,
    capacity: 5000,
    tags: ["Afrobeats", "Live Music", "Cultural"],
    reviews: [],
  },
  {
    id: "evt-012",
    title: "World Street Food Championship",
    category: "Cultural",
    date: "2026-10-03",
    time: "12:00",
    endTime: "22:00",
    venue: "Pier 17",
    location: "New York, NY",
    city: "New York",
    country: "USA",
    organizer: "Street Food Foundation",
    description:
      "120 chefs from 40 countries compete for the world's most coveted street food title.",
    longDescription:
      "The World Street Food Championship brings 120 chefs representing 40 countries to a single iconic venue for a weekend celebrating the most democratic form of cooking on earth.\n\nVisitors receive a digital tasting passport loaded with credits to sample dishes across every participating vendor. A panel of international judges runs a concurrent competition awarding prizes across 12 categories.",
    image: U("1504674900247-0877df9cc836"),
    gallery: [],
    ticketTiers: [
      { id: "t1", name: "Day Pass", price: 25, available: 1200, description: "All-day access + tasting passport with 10 credits" },
      { id: "t2", name: "Gourmet Pass", price: 65, available: 300, description: "Day access + 25 credits + VIP seating zone" },
    ],
    hasReservedSeating: false,
    isFeatured: false,
    isTrending: false,
    rating: 4.5,
    reviewCount: 678,
    capacity: 10000,
    tags: ["Food", "Cultural", "Competition", "Family"],
    reviews: [],
  },
]

export const FEATURED_EVENTS = EVENTS.filter((e) => e.isFeatured)
export const TRENDING_EVENTS = EVENTS.filter((e) => e.isTrending)

export const SAMPLE_BOOKINGS: Booking[] = [
  {
    id: "bk-9x2k",
    eventId: "evt-001",
    tierId: "t2",
    tierName: "VIP",
    quantity: 2,
    totalPrice: 698,
    purchasedAt: "2026-08-10T14:32:00Z",
    attendeeName: "Jordan Lee",
    attendeeEmail: "jordan@example.com",
  },
  {
    id: "bk-4m7p",
    eventId: "evt-002",
    tierId: "t2",
    tierName: "Lower Bowl",
    quantity: 1,
    totalPrice: 120,
    purchasedAt: "2026-08-15T09:11:00Z",
    seats: ["F-12"],
    attendeeName: "Jordan Lee",
    attendeeEmail: "jordan@example.com",
  },
  {
    id: "bk-8z1q",
    eventId: "evt-011",
    tierId: "t1",
    tierName: "Floor GA",
    quantity: 3,
    totalPrice: 165,
    purchasedAt: "2026-08-20T19:55:00Z",
    attendeeName: "Jordan Lee",
    attendeeEmail: "jordan@example.com",
  },
]

export function formatDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
    year: "numeric",
  })
}

export function formatShortDate(dateStr: string) {
  return new Date(dateStr).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  })
}

export function formatPrice(price: number) {
  if (price === 0) return "Free"
  return `$${price.toLocaleString()}`
}

export function getMinPrice(event: Event) {
  const prices = event.ticketTiers.map((t) => t.price)
  return Math.min(...prices)
}
