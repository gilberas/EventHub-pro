import { useState } from "react"
import { type Event, formatDate, formatPrice, getMinPrice } from "../data/events"

type Props = {
  event: Event
  onBack: () => void
  onCheckout: (tierId: string, quantity: number) => void
}

function StarRating({ rating, size = "sm" }: { rating: number; size?: "sm" | "lg" }) {
  const s = size === "lg" ? "w-5 h-5" : "w-3.5 h-3.5"
  return (
    <div className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <svg key={star} viewBox="0 0 24 24" className={`${s} ${star <= Math.round(rating) ? "fill-warning text-warning" : "fill-none text-muted"}`} stroke="currentColor" strokeWidth="1.5">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
      ))}
    </div>
  )
}

export default function EventDetailPage({ event, onBack, onCheckout }: Props) {
  const [selectedTier, setSelectedTier] = useState(event.ticketTiers[0]?.id ?? "")
  const [quantity, setQuantity] = useState(1)
  const [galleryIdx, setGalleryIdx] = useState(0)

  const tier = event.ticketTiers.find((t) => t.id === selectedTier)
  const subtotal = tier ? tier.price * quantity : 0
  const serviceFee = Math.round(subtotal * 0.12)
  const total = subtotal + serviceFee

  const allImages = [event.image, ...event.gallery]

  return (
    <div className="min-h-screen bg-void pt-16">
      {/* Hero image */}
      <div className="relative h-72 sm:h-96 overflow-hidden bg-ghost">
        <img
          src={allImages[galleryIdx]}
          alt={event.title}
          className="w-full h-full object-cover transition-opacity duration-300"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-void via-void/30 to-transparent" />

        {/* Back button */}
        <button
          onClick={onBack}
          className="absolute top-6 left-6 flex items-center gap-2 px-4 py-2 rounded-xl bg-black/40 backdrop-blur-sm border border-white/15 text-white text-sm hover:bg-black/60 transition-colors"
        >
          <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
          Back
        </button>

        {/* Gallery thumbnails */}
        {allImages.length > 1 && (
          <div className="absolute bottom-6 left-6 flex gap-2">
            {allImages.map((img, i) => (
              <button
                key={i}
                onClick={() => setGalleryIdx(i)}
                className={`w-10 h-10 rounded-lg overflow-hidden border-2 transition-all ${i === galleryIdx ? "border-purple scale-110" : "border-white/20"}`}
              >
                <img src={img} alt="" className="w-full h-full object-cover" />
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
          {/* Left: event info */}
          <div className="lg:col-span-2 space-y-8">
            {/* Category + tags */}
            <div className="flex flex-wrap items-center gap-2">
              <span className="px-3 py-1 rounded-full text-xs font-semibold gradient-btn text-white">
                {event.category}
              </span>
              {event.tags.map((tag) => (
                <span key={tag} className="px-3 py-1 rounded-full text-xs font-medium bg-surface border border-rim text-muted">
                  {tag}
                </span>
              ))}
            </div>

            {/* Title */}
            <div>
              <h1 className="font-display text-4xl sm:text-5xl font-bold text-white leading-tight mb-3">
                {event.title}
              </h1>
              <div className="flex items-center gap-3">
                <StarRating rating={event.rating} size="lg" />
                <span className="text-white font-semibold">{event.rating}</span>
                <span className="text-muted text-sm">({event.reviewCount.toLocaleString()} reviews)</span>
              </div>
            </div>

            {/* Event meta */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {[
                {
                  icon: <><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></>,
                  label: "Date & Time",
                  value: `${formatDate(event.date)} · ${event.time}–${event.endTime}`,
                },
                {
                  icon: <><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></>,
                  label: "Venue",
                  value: `${event.venue}, ${event.location}`,
                },
                {
                  icon: <><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></>,
                  label: "Organizer",
                  value: event.organizer,
                },
                {
                  icon: <><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></>,
                  label: "Capacity",
                  value: `${event.capacity.toLocaleString()} attendees`,
                },
              ].map((item) => (
                <div key={item.label} className="flex gap-3 p-4 rounded-xl bg-surface border border-rim">
                  <svg viewBox="0 0 24 24" className="w-5 h-5 text-purple shrink-0 mt-0.5" fill="none" stroke="currentColor" strokeWidth="1.8">
                    {item.icon}
                  </svg>
                  <div>
                    <p className="text-muted text-xs font-medium uppercase tracking-wide mb-0.5">{item.label}</p>
                    <p className="text-white text-sm font-medium">{item.value}</p>
                  </div>
                </div>
              ))}
            </div>

            {/* Description */}
            <div>
              <h2 className="font-display text-xl font-semibold text-white mb-4">About This Event</h2>
              <div className="text-white/70 leading-relaxed space-y-4">
                {event.longDescription.split("\n\n").map((para, i) => (
                  <p key={i}>{para}</p>
                ))}
              </div>
            </div>

            {/* Seating notice */}
            {event.hasReservedSeating && (
              <div className="flex gap-3 p-4 rounded-xl bg-purple/10 border border-purple/25">
                <svg viewBox="0 0 24 24" className="w-5 h-5 text-purple shrink-0 mt-0.5" fill="none" stroke="currentColor" strokeWidth="1.8">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div>
                  <p className="text-white text-sm font-medium mb-0.5">Reserved Seating Available</p>
                  <p className="text-white/60 text-xs">You'll be able to choose your exact seat after selecting a ticket tier.</p>
                </div>
              </div>
            )}

            {/* Reviews */}
            {event.reviews.length > 0 && (
              <div>
                <h2 className="font-display text-xl font-semibold text-white mb-5">Reviews</h2>
                <div className="space-y-4">
                  {event.reviews.map((review, i) => (
                    <div key={i} className="p-5 rounded-xl bg-surface border border-rim">
                      <div className="flex items-center justify-between mb-3">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full gradient-btn flex items-center justify-center text-white text-xs font-bold">
                            {review.author[0]}
                          </div>
                          <span className="text-white font-medium text-sm">{review.author}</span>
                        </div>
                        <StarRating rating={review.rating} />
                      </div>
                      <p className="text-white/70 text-sm leading-relaxed">{review.text}</p>
                      <p className="text-muted text-xs mt-3">{new Date(review.date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}</p>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right: Ticket selector */}
          <div className="lg:col-span-1">
            <div className="sticky top-24 rounded-2xl bg-surface border border-rim p-6 space-y-6">
              <div>
                <p className="text-muted text-xs font-medium uppercase tracking-widest mb-1">Tickets</p>
                <p className="font-display text-2xl font-semibold text-white">from {formatPrice(getMinPrice(event))}</p>
              </div>

              {/* Tier selection */}
              <div className="space-y-3">
                {event.ticketTiers.map((tier) => (
                  <button
                    key={tier.id}
                    onClick={() => setSelectedTier(tier.id)}
                    className={`w-full p-4 rounded-xl border text-left transition-all ${
                      selectedTier === tier.id
                        ? "border-purple bg-purple/10"
                        : "border-rim bg-ghost hover:border-white/20"
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1">
                      <span className={`font-semibold text-sm ${selectedTier === tier.id ? "text-white" : "text-white/80"}`}>
                        {tier.name}
                      </span>
                      <span className={`font-bold text-sm ${selectedTier === tier.id ? "text-purple-glow" : "text-white/70"}`}>
                        {formatPrice(tier.price)}
                      </span>
                    </div>
                    <p className="text-muted text-xs">{tier.description}</p>
                    <p className={`text-xs mt-2 ${tier.available < 50 ? "text-warning" : "text-success"}`}>
                      {tier.available < 50 ? `⚠ Only ${tier.available} left` : `${tier.available} available`}
                    </p>
                  </button>
                ))}
              </div>

              {/* Quantity */}
              <div>
                <label className="text-muted text-xs font-medium uppercase tracking-wide block mb-2">Quantity</label>
                <div className="flex items-center gap-3">
                  <button
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    className="w-10 h-10 rounded-lg bg-ghost border border-rim text-white hover:border-purple/40 transition-colors flex items-center justify-center"
                  >
                    <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M5 12h14"/>
                    </svg>
                  </button>
                  <span className="text-white font-semibold text-lg w-8 text-center">{quantity}</span>
                  <button
                    onClick={() => setQuantity(Math.min(8, quantity + 1))}
                    className="w-10 h-10 rounded-lg bg-ghost border border-rim text-white hover:border-purple/40 transition-colors flex items-center justify-center"
                  >
                    <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M12 5v14M5 12h14"/>
                    </svg>
                  </button>
                </div>
              </div>

              {/* Price breakdown */}
              {tier && (
                <div className="space-y-2 border-t border-rim pt-4">
                  <div className="flex justify-between text-sm text-muted">
                    <span>{formatPrice(tier.price)} × {quantity}</span>
                    <span className="text-white">{formatPrice(subtotal)}</span>
                  </div>
                  <div className="flex justify-between text-sm text-muted">
                    <span>Service fee</span>
                    <span className="text-white">{formatPrice(serviceFee)}</span>
                  </div>
                  <div className="flex justify-between font-bold text-white border-t border-rim pt-2">
                    <span>Total</span>
                    <span className="gradient-text text-lg">{formatPrice(total)}</span>
                  </div>
                </div>
              )}

              <button
                onClick={() => onCheckout(selectedTier, quantity)}
                className="gradient-btn w-full py-4 rounded-xl text-white font-semibold text-sm tracking-wide"
              >
                {event.hasReservedSeating ? "Choose Seats" : "Proceed to Checkout"}
              </button>

              <p className="text-muted text-xs text-center">
                Secure checkout · Instant confirmation · Mobile tickets
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
