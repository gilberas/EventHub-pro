import { EVENTS, SAMPLE_BOOKINGS, formatDate, formatPrice, type Booking } from "../data/events"

type Props = {
  onSelectEvent: (eventId: string) => void
}

function TicketCard({ booking, onViewEvent }: { booking: Booking; onViewEvent: () => void }) {
  const event = EVENTS.find((e) => e.id === booking.eventId)
  if (!event) return null

  const isUpcoming = new Date(event.date) > new Date()
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=EVENTHUB-${booking.id.toUpperCase()}&bgcolor=111120&color=a855f7&margin=8`

  return (
    <div className={`rounded-2xl border overflow-hidden ${isUpcoming ? "border-purple/25 glow-purple" : "border-rim opacity-70"}`} style={{ background: "#13131f" }}>
      {/* Header */}
      <div className="relative h-32 overflow-hidden bg-ghost">
        <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-[#13131f] to-transparent" />
        {isUpcoming ? (
          <div className="absolute top-3 right-3">
            <span className="px-2.5 py-1 rounded-full text-xs font-semibold gradient-btn text-white">Upcoming</span>
          </div>
        ) : (
          <div className="absolute top-3 right-3">
            <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-white/10 text-white/60 border border-white/15">Past</span>
          </div>
        )}
        <div className="absolute bottom-3 left-4">
          <h3 className="font-display font-bold text-white text-base leading-snug">{event.title}</h3>
        </div>
      </div>

      {/* Dashed separator */}
      <div className="relative flex items-center mx-5">
        <div className="absolute -left-5 w-4 h-4 rounded-full bg-void border border-purple/20" />
        <div className="flex-1 border-t border-dashed border-white/10" />
        <div className="absolute -right-5 w-4 h-4 rounded-full bg-void border border-purple/20" />
      </div>

      {/* Body */}
      <div className="p-5 flex items-start gap-4">
        <div className="flex-1 space-y-3">
          <div className="grid grid-cols-2 gap-3 text-xs">
            <div>
              <p className="text-muted uppercase tracking-wide mb-0.5">Date</p>
              <p className="text-white font-medium">{formatDate(event.date)}</p>
            </div>
            <div>
              <p className="text-muted uppercase tracking-wide mb-0.5">Time</p>
              <p className="text-white font-medium">{event.time}</p>
            </div>
            <div>
              <p className="text-muted uppercase tracking-wide mb-0.5">Tier</p>
              <p className="text-white font-medium">{booking.tierName}</p>
            </div>
            <div>
              <p className="text-muted uppercase tracking-wide mb-0.5">Qty</p>
              <p className="text-white font-medium">{booking.quantity}</p>
            </div>
          </div>
          {booking.seats && booking.seats.length > 0 && (
            <div>
              <p className="text-muted text-xs uppercase tracking-wide mb-1">Seats</p>
              <div className="flex gap-1.5 flex-wrap">
                {booking.seats.map((s) => (
                  <span key={s} className="px-2 py-0.5 rounded-full bg-purple/15 text-purple-glow text-xs border border-purple/20">
                    {s}
                  </span>
                ))}
              </div>
            </div>
          )}
          <div className="flex items-center justify-between">
            <div>
              <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Booking</p>
              <p className="text-white text-xs font-mono font-bold tracking-widest">{booking.id.toUpperCase()}</p>
            </div>
            <p className="gradient-text font-bold text-sm">{formatPrice(booking.totalPrice)}</p>
          </div>
          {isUpcoming && (
            <button
              onClick={onViewEvent}
              className="text-purple-glow text-xs font-medium hover:text-white transition-colors flex items-center gap-1"
            >
              View event details
              <svg viewBox="0 0 24 24" className="w-3 h-3" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
            </button>
          )}
        </div>

        {/* QR */}
        <div className="shrink-0 flex flex-col items-center gap-1.5">
          <div className="w-20 h-20 rounded-lg overflow-hidden bg-ghost border border-rim">
            <img src={qrUrl} alt="QR" className="w-full h-full" />
          </div>
          <p className="text-muted text-[10px] text-center">Entry QR</p>
        </div>
      </div>
    </div>
  )
}

export default function MyTicketsPage({ onSelectEvent }: Props) {
  const upcoming = SAMPLE_BOOKINGS.filter((b) => {
    const event = EVENTS.find((e) => e.id === b.eventId)
    return event && new Date(event.date) > new Date()
  })
  const past = SAMPLE_BOOKINGS.filter((b) => {
    const event = EVENTS.find((e) => e.id === b.eventId)
    return event && new Date(event.date) <= new Date()
  })

  return (
    <div className="min-h-screen bg-void pt-16">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 py-10">
        <div className="mb-10">
          <p className="text-purple text-xs font-semibold uppercase tracking-widest mb-1">Your Account</p>
          <h1 className="font-display text-4xl font-bold text-white">My Tickets</h1>
          <p className="text-muted mt-2">{SAMPLE_BOOKINGS.length} booking{SAMPLE_BOOKINGS.length !== 1 ? "s" : ""} · Jordan Lee</p>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-3 gap-4 mb-10">
          {[
            { label: "Total Bookings", value: SAMPLE_BOOKINGS.length.toString(), icon: "🎫" },
            { label: "Upcoming Events", value: upcoming.length.toString(), icon: "📅" },
            { label: "Total Spent", value: formatPrice(SAMPLE_BOOKINGS.reduce((a, b) => a + b.totalPrice, 0)), icon: "💳" },
          ].map((stat) => (
            <div key={stat.label} className="p-4 rounded-xl bg-surface border border-rim text-center">
              <div className="text-2xl mb-1">{stat.icon}</div>
              <div className="text-white font-bold text-xl font-display">{stat.value}</div>
              <div className="text-muted text-xs mt-0.5">{stat.label}</div>
            </div>
          ))}
        </div>

        {upcoming.length > 0 && (
          <section className="mb-10">
            <h2 className="font-display text-xl font-semibold text-white mb-5 flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-success inline-block animate-pulse" />
              Upcoming
            </h2>
            <div className="space-y-5">
              {upcoming.map((b) => (
                <TicketCard key={b.id} booking={b} onViewEvent={() => onSelectEvent(b.eventId)} />
              ))}
            </div>
          </section>
        )}

        {past.length > 0 && (
          <section>
            <h2 className="font-display text-xl font-semibold text-white mb-5 flex items-center gap-2">
              <span className="w-2 h-2 rounded-full bg-muted inline-block" />
              Past Events
            </h2>
            <div className="space-y-5">
              {past.map((b) => (
                <TicketCard key={b.id} booking={b} onViewEvent={() => onSelectEvent(b.eventId)} />
              ))}
            </div>
          </section>
        )}

        {SAMPLE_BOOKINGS.length === 0 && (
          <div className="text-center py-24">
            <div className="text-6xl mb-4">🎫</div>
            <h3 className="font-display text-xl text-white mb-2">No tickets yet</h3>
            <p className="text-muted text-sm">Discover events and get your first ticket.</p>
          </div>
        )}
      </div>
    </div>
  )
}
