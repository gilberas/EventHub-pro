import { type Event, formatDate, formatPrice } from "../data/events"

type Props = {
  event: Event
  tierId: string
  quantity: number
  bookingId: string
  seats: string[]
  onHome: () => void
  onMyTickets: () => void
}

export default function ConfirmationPage({ event, tierId, quantity, bookingId, seats, onHome, onMyTickets }: Props) {
  const tier = event.ticketTiers.find((t) => t.id === tierId)!
  const total = Math.round(tier.price * quantity * 1.12)
  const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=EVENTHUB-${bookingId.toUpperCase()}&bgcolor=111120&color=a855f7&margin=10`

  return (
    <div className="min-h-screen bg-void flex items-center justify-center pt-16 px-4">
      <div className="w-full max-w-lg">
        {/* Success animation */}
        <div className="text-center mb-8">
          <div className="relative inline-flex">
            <div className="w-24 h-24 rounded-full gradient-btn flex items-center justify-center mb-6 glow-purple">
              <svg viewBox="0 0 24 24" className="w-12 h-12 text-white" fill="none" stroke="currentColor" strokeWidth="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
            <div className="absolute inset-0 rounded-full border-2 border-purple/30 animate-ping" />
          </div>
          <h1 className="font-display text-4xl font-bold text-white mb-2">You're In!</h1>
          <p className="text-muted text-base">Your booking is confirmed. See you at the event.</p>
        </div>

        {/* Ticket card */}
        <div className="rounded-3xl overflow-hidden border border-purple/30 glow-purple" style={{ background: "#13131f" }}>
          {/* Ticket header */}
          <div className="relative h-40 overflow-hidden bg-ghost">
            <img src={event.image} alt={event.title} className="w-full h-full object-cover opacity-70" />
            <div className="absolute inset-0 bg-gradient-to-t from-[#13131f] to-transparent" />
            <div className="absolute bottom-4 left-6 right-6">
              <h2 className="font-display text-xl font-bold text-white leading-snug">{event.title}</h2>
              <p className="text-white/60 text-sm mt-0.5">{event.venue} · {event.city}</p>
            </div>
          </div>

          {/* Dashed separator */}
          <div className="relative flex items-center mx-6">
            <div className="absolute -left-6 w-5 h-5 rounded-full bg-void border border-purple/30" />
            <div className="flex-1 border-t border-dashed border-white/15" />
            <div className="absolute -right-6 w-5 h-5 rounded-full bg-void border border-purple/30" />
          </div>

          {/* Ticket body */}
          <div className="p-6 flex gap-6">
            <div className="flex-1 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Date</p>
                  <p className="text-white text-sm font-medium">{formatDate(event.date)}</p>
                </div>
                <div>
                  <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Time</p>
                  <p className="text-white text-sm font-medium">{event.time}</p>
                </div>
                <div>
                  <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Tier</p>
                  <p className="text-white text-sm font-medium">{tier.name}</p>
                </div>
                <div>
                  <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Qty</p>
                  <p className="text-white text-sm font-medium">{quantity} ticket{quantity > 1 ? "s" : ""}</p>
                </div>
              </div>
              {seats.length > 0 && (
                <div>
                  <p className="text-muted text-xs uppercase tracking-wide mb-1.5">Seats</p>
                  <div className="flex flex-wrap gap-1.5">
                    {seats.map((s) => (
                      <span key={s} className="px-2.5 py-1 rounded-full bg-purple/15 text-purple-glow text-xs border border-purple/25">
                        {s}
                      </span>
                    ))}
                  </div>
                </div>
              )}
              <div>
                <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Booking ID</p>
                <p className="text-white text-sm font-mono font-bold tracking-widest">{bookingId.toUpperCase()}</p>
              </div>
              <div>
                <p className="text-muted text-xs uppercase tracking-wide mb-0.5">Total Paid</p>
                <p className="gradient-text font-bold text-lg">{formatPrice(total)}</p>
              </div>
            </div>

            {/* QR code */}
            <div className="flex flex-col items-center gap-2 shrink-0">
              <div className="w-28 h-28 rounded-xl overflow-hidden bg-ghost border border-rim flex items-center justify-center">
                <img
                  src={qrUrl}
                  alt="QR Code"
                  className="w-full h-full"
                  onError={(e) => {
                    const t = e.target as HTMLImageElement
                    t.style.display = "none"
                  }}
                />
              </div>
              <p className="text-muted text-[10px] text-center leading-tight">Scan at<br/>the door</p>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="flex gap-3 mt-6">
          <button
            onClick={onMyTickets}
            className="flex-1 gradient-btn py-3.5 rounded-xl text-white font-semibold text-sm"
          >
            View My Tickets
          </button>
          <button
            onClick={onHome}
            className="flex-1 py-3.5 rounded-xl text-white font-semibold text-sm border border-rim bg-ghost hover:bg-white/5 transition-colors"
          >
            Discover More
          </button>
        </div>

        <p className="text-center text-muted text-xs mt-4">
          A confirmation has been sent to jordan@example.com
        </p>
      </div>
    </div>
  )
}
