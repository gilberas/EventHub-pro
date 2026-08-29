import { useState, useMemo } from "react"
import { EVENTS, CATEGORIES, FEATURED_EVENTS, TRENDING_EVENTS, formatShortDate, formatPrice, getMinPrice, type Event } from "../data/events"

type Props = {
  onSelectEvent: (event: Event) => void
}

function StarRating({ rating }: { rating: number }) {
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <svg key={star} viewBox="0 0 24 24" className={`w-3.5 h-3.5 ${star <= Math.round(rating) ? "fill-warning text-warning" : "fill-none text-muted"}`} stroke="currentColor" strokeWidth="1.5">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
        </svg>
      ))}
    </div>
  )
}

function EventCard({ event, onSelect, size = "normal" }: { event: Event; onSelect: () => void; size?: "normal" | "large" }) {
  const minPrice = getMinPrice(event)
  return (
    <button
      onClick={onSelect}
      className={`card-hover group w-full text-left rounded-2xl overflow-hidden border border-rim bg-surface flex flex-col ${size === "large" ? "h-96" : ""}`}
    >
      <div className={`relative overflow-hidden bg-ghost ${size === "large" ? "h-56" : "aspect-video"}`}>
        <img
          src={event.image}
          alt={event.title}
          className="img-hover w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
        <div className="absolute top-3 left-3">
          <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-black/50 text-white border border-white/20 backdrop-blur-sm">
            {event.category}
          </span>
        </div>
        {event.isTrending && (
          <div className="absolute top-3 right-3">
            <span className="px-2.5 py-1 rounded-full text-xs font-semibold gradient-btn text-white">
              🔥 Hot
            </span>
          </div>
        )}
        <div className="absolute bottom-3 left-3 right-3 flex items-end justify-between">
          <div className="flex items-center gap-1.5 text-white/80 text-xs">
            <svg viewBox="0 0 24 24" className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            {event.city}
          </div>
          <div className="text-white font-semibold text-sm">
            {minPrice === 0 ? "Free" : `from ${formatPrice(minPrice)}`}
          </div>
        </div>
      </div>
      <div className="p-4 flex flex-col gap-2 flex-1">
        <h3 className="font-display font-semibold text-white text-base leading-snug line-clamp-2 group-hover:text-purple-glow transition-colors">
          {event.title}
        </h3>
        <div className="flex items-center gap-1.5 text-muted text-xs">
          <svg viewBox="0 0 24 24" className="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          {formatShortDate(event.date)} · {event.time}
        </div>
        <div className="flex items-center gap-1.5 text-muted text-xs">
          <svg viewBox="0 0 24 24" className="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
          </svg>
          <span className="truncate">{event.venue}</span>
        </div>
        <div className="flex items-center justify-between mt-auto pt-2">
          <div className="flex items-center gap-1.5">
            <StarRating rating={event.rating} />
            <span className="text-muted text-xs">{event.rating} ({event.reviewCount.toLocaleString()})</span>
          </div>
        </div>
      </div>
    </button>
  )
}

function HeroEvent({ event, onSelect }: { event: Event; onSelect: () => void }) {
  return (
    <div className="relative w-full h-[90vh] min-h-[600px] overflow-hidden">
      <img
        src={event.image}
        alt={event.title}
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="hero-overlay absolute inset-0" />
      <div className="absolute inset-0 flex flex-col justify-end px-6 sm:px-12 pb-16 max-w-5xl">
        <div className="flex items-center gap-3 mb-5">
          <span className="px-3 py-1 rounded-full text-xs font-semibold gradient-btn text-white">
            ✦ Featured Event
          </span>
          <span className="px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white border border-white/20 backdrop-blur-sm">
            {event.category}
          </span>
        </div>
        <h1 className="font-display text-5xl sm:text-7xl font-bold text-white leading-none tracking-tight mb-4">
          {event.title}
        </h1>
        <p className="text-white/70 text-lg max-w-lg leading-relaxed mb-3">
          {event.description}
        </p>
        <div className="flex flex-wrap items-center gap-4 text-white/60 text-sm mb-8">
          <span className="flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            {formatShortDate(event.date)}
          </span>
          <span className="flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
            {event.venue}, {event.city}
          </span>
          <span className="flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
            from {formatPrice(getMinPrice(event))}
          </span>
        </div>
        <div className="flex items-center gap-4">
          <button
            onClick={onSelect}
            className="gradient-btn px-8 py-3.5 rounded-xl font-semibold text-white text-sm tracking-wide"
          >
            Get Tickets
          </button>
          <button
            onClick={onSelect}
            className="px-8 py-3.5 rounded-xl font-semibold text-white text-sm border border-white/20 bg-white/5 backdrop-blur-sm hover:bg-white/10 transition-colors"
          >
            View Details
          </button>
        </div>
      </div>
    </div>
  )
}

export default function HomePage({ onSelectEvent }: Props) {
  const [activeCategory, setActiveCategory] = useState("All")
  const [search, setSearch] = useState("")

  const filteredEvents = useMemo(() => {
    return EVENTS.filter((e) => {
      const matchCat = activeCategory === "All" || e.category === activeCategory
      const matchSearch =
        search.trim() === "" ||
        e.title.toLowerCase().includes(search.toLowerCase()) ||
        e.city.toLowerCase().includes(search.toLowerCase()) ||
        e.venue.toLowerCase().includes(search.toLowerCase()) ||
        e.organizer.toLowerCase().includes(search.toLowerCase())
      return matchCat && matchSearch
    })
  }, [activeCategory, search])

  const heroEvent = FEATURED_EVENTS[0]

  return (
    <div className="min-h-screen bg-void">
      {/* Hero */}
      <HeroEvent event={heroEvent} onSelect={() => onSelectEvent(heroEvent)} />

      {/* Search + Categories */}
      <div className="sticky top-16 z-40 bg-void/90 backdrop-blur-md border-b border-rim">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row gap-3 items-center">
          {/* Search */}
          <div className="relative flex-1 max-w-sm w-full">
            <svg viewBox="0 0 24 24" className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input
              type="text"
              placeholder="Search events, artists, venues…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full bg-surface border border-rim rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder:text-muted focus:outline-none focus:border-purple/60 transition-colors"
            />
          </div>

          {/* Category pills */}
          <div className="flex items-center gap-2 overflow-x-auto py-0.5 shrink-0">
            {CATEGORIES.map((cat) => (
              <button
                key={cat}
                onClick={() => setActiveCategory(cat)}
                className={`shrink-0 px-4 py-2 rounded-full text-xs font-medium transition-all ${
                  activeCategory === cat
                    ? "gradient-btn text-white shadow-lg"
                    : "bg-surface border border-rim text-muted hover:text-white hover:border-white/20"
                }`}
              >
                {cat}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12 space-y-16">
        {/* Search results banner */}
        {search.trim() !== "" && (
          <div className="text-muted text-sm">
            {filteredEvents.length} result{filteredEvents.length !== 1 ? "s" : ""} for{" "}
            <span className="text-white font-medium">"{search}"</span>
            <button onClick={() => setSearch("")} className="ml-3 text-purple hover:text-purple-glow">Clear</button>
          </div>
        )}

        {/* Featured Events */}
        {activeCategory === "All" && search.trim() === "" ? (
          <>
            <section>
              <div className="flex items-end justify-between mb-6">
                <div>
                  <p className="text-purple text-xs font-semibold uppercase tracking-widest mb-1">Handpicked</p>
                  <h2 className="font-display text-3xl font-semibold text-white">Featured Events</h2>
                </div>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                {FEATURED_EVENTS.map((event) => (
                  <EventCard key={event.id} event={event} onSelect={() => onSelectEvent(event)} />
                ))}
              </div>
            </section>

            {/* Trending */}
            <section>
              <div className="flex items-end justify-between mb-6">
                <div>
                  <p className="text-pink text-xs font-semibold uppercase tracking-widest mb-1">Trending Now</p>
                  <h2 className="font-display text-3xl font-semibold text-white">Hot This Week</h2>
                </div>
              </div>
              <div className="flex gap-5 overflow-x-auto pb-4">
                {TRENDING_EVENTS.map((event) => (
                  <div key={event.id} className="shrink-0 w-72">
                    <EventCard event={event} onSelect={() => onSelectEvent(event)} />
                  </div>
                ))}
              </div>
            </section>

            {/* All upcoming events */}
            <section>
              <div className="mb-6">
                <p className="text-muted text-xs font-semibold uppercase tracking-widest mb-1">Up Next</p>
                <h2 className="font-display text-3xl font-semibold text-white">All Events</h2>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                {EVENTS.map((event) => (
                  <EventCard key={event.id} event={event} onSelect={() => onSelectEvent(event)} />
                ))}
              </div>
            </section>
          </>
        ) : (
          <section>
            <div className="mb-6">
              <h2 className="font-display text-3xl font-semibold text-white">
                {activeCategory === "All" ? "All Events" : activeCategory}
              </h2>
              <p className="text-muted text-sm mt-1">{filteredEvents.length} events</p>
            </div>
            {filteredEvents.length === 0 ? (
              <div className="text-center py-24">
                <div className="text-6xl mb-4">🔍</div>
                <h3 className="font-display text-xl text-white mb-2">No events found</h3>
                <p className="text-muted text-sm">Try a different search or browse all categories</p>
                <button
                  onClick={() => { setSearch(""); setActiveCategory("All") }}
                  className="mt-6 gradient-btn px-6 py-2.5 rounded-xl text-white text-sm font-medium"
                >
                  Browse All Events
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                {filteredEvents.map((event) => (
                  <EventCard key={event.id} event={event} onSelect={() => onSelectEvent(event)} />
                ))}
              </div>
            )}
          </section>
        )}
      </div>

      {/* Footer */}
      <footer className="border-t border-rim mt-20 py-12 px-6">
        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-6 text-muted text-sm">
          <div className="flex items-center gap-2">
            <div className="w-6 h-6 rounded-md gradient-btn flex items-center justify-center">
              <svg viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2.5" className="w-3 h-3">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
              </svg>
            </div>
            <span className="font-display font-medium text-white">EventHub<span className="gradient-text">Pro</span></span>
          </div>
          <div className="flex gap-6 text-xs">
            <span className="hover:text-white cursor-pointer transition-colors">Terms</span>
            <span className="hover:text-white cursor-pointer transition-colors">Privacy</span>
            <span className="hover:text-white cursor-pointer transition-colors">Support</span>
            <span className="hover:text-white cursor-pointer transition-colors">For Organizers</span>
          </div>
          <p className="text-xs">© 2026 EventHub Pro. All rights reserved.</p>
        </div>
      </footer>
    </div>
  )
}
