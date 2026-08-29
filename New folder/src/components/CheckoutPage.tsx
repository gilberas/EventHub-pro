import { useState, useMemo } from "react"
import { type Event, formatPrice } from "../data/events"

type Props = {
  event: Event
  tierId: string
  quantity: number
  onConfirm: (bookingId: string, seats: string[]) => void
  onBack: () => void
}

const ROWS = ["A", "B", "C", "D", "E", "F", "G", "H", "J", "K"]
const COLS = Array.from({ length: 18 }, (_, i) => i + 1)

const TAKEN_SEATS = new Set([
  "A-1","A-2","B-5","B-6","B-7","C-3","C-8","C-12","D-2","D-9","D-15",
  "E-4","E-10","E-14","F-1","F-7","F-13","G-6","G-11","H-3","H-9","H-16",
  "J-5","J-8","J-12","K-2","K-7","K-15","A-14","B-15","C-16","D-17","E-18",
])

function SeatMap({ quantity, onSeatsSelected }: { quantity: number; onSeatsSelected: (seats: string[]) => void }) {
  const [selected, setSelected] = useState<string[]>([])

  const toggle = (seatId: string) => {
    if (TAKEN_SEATS.has(seatId)) return
    setSelected((prev) => {
      let next: string[]
      if (prev.includes(seatId)) {
        next = prev.filter((s) => s !== seatId)
      } else if (prev.length < quantity) {
        next = [...prev, seatId]
      } else {
        next = [...prev.slice(1), seatId]
      }
      onSeatsSelected(next)
      return next
    })
  }

  return (
    <div className="space-y-4">
      {/* Stage */}
      <div className="w-full py-3 rounded-xl border border-purple/30 bg-purple/10 text-center text-purple-glow text-xs font-semibold tracking-widest uppercase">
        Stage
      </div>

      {/* Seat grid */}
      <div className="overflow-x-auto">
        <div className="inline-block min-w-full">
          {ROWS.map((row) => (
            <div key={row} className="flex items-center gap-1 mb-1">
              <span className="text-muted text-xs w-5 text-center shrink-0">{row}</span>
              {COLS.map((col) => {
                const id = `${row}-${col}`
                const taken = TAKEN_SEATS.has(id)
                const sel = selected.includes(id)
                return (
                  <button
                    key={id}
                    onClick={() => toggle(id)}
                    title={taken ? "Unavailable" : sel ? `Selected: ${id}` : id}
                    className={`w-6 h-6 rounded-sm text-xs transition-all ${
                      taken
                        ? "bg-white/5 cursor-not-allowed"
                        : sel
                        ? "bg-purple shadow-lg shadow-purple/40 scale-110"
                        : "bg-elevated hover:bg-purple/40 seat-available"
                    }`}
                  />
                )
              })}
              <span className="text-muted text-xs w-5 text-center shrink-0">{row}</span>
            </div>
          ))}
          {/* Column numbers */}
          <div className="flex items-center gap-1 mt-2 ml-6">
            {COLS.map((col) => (
              <span key={col} className="w-6 text-center text-muted text-[9px]">{col}</span>
            ))}
          </div>
        </div>
      </div>

      {/* Legend */}
      <div className="flex items-center gap-6 text-xs text-muted">
        <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded-sm bg-elevated inline-block" /> Available</span>
        <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded-sm bg-purple inline-block" /> Selected</span>
        <span className="flex items-center gap-1.5"><span className="w-4 h-4 rounded-sm bg-white/5 inline-block" /> Taken</span>
      </div>

      {selected.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {selected.map((s) => (
            <span key={s} className="px-3 py-1 rounded-full bg-purple/20 text-purple-glow text-xs font-medium border border-purple/30">
              Seat {s}
            </span>
          ))}
          {selected.length < quantity && (
            <span className="px-3 py-1 rounded-full bg-ghost text-muted text-xs border border-rim">
              Select {quantity - selected.length} more
            </span>
          )}
        </div>
      )}
    </div>
  )
}

export default function CheckoutPage({ event, tierId, quantity, onConfirm, onBack }: Props) {
  const tier = event.ticketTiers.find((t) => t.id === tierId)!
  const subtotal = tier.price * quantity
  const serviceFee = Math.round(subtotal * 0.12)
  const total = subtotal + serviceFee

  const [step, setStep] = useState<"seats" | "payment">(event.hasReservedSeating ? "seats" : "payment")
  const [seats, setSeats] = useState<string[]>([])
  const [form, setForm] = useState({ name: "Jordan Lee", email: "jordan@example.com", card: "", expiry: "", cvv: "" })
  const [processing, setProcessing] = useState(false)

  const seatsReady = !event.hasReservedSeating || seats.length === quantity

  const handlePay = () => {
    if (!form.card || !form.expiry || !form.cvv) return
    setProcessing(true)
    setTimeout(() => {
      const bookingId = "bk-" + Math.random().toString(36).slice(2, 6)
      onConfirm(bookingId, seats)
    }, 1800)
  }

  const formField = (label: string, key: keyof typeof form, placeholder: string, extra?: string) => (
    <div>
      <label className="text-muted text-xs font-medium uppercase tracking-wide block mb-1.5">{label}</label>
      <input
        type="text"
        placeholder={placeholder}
        value={form[key]}
        onChange={(e) => setForm((f) => ({ ...f, [key]: e.target.value }))}
        className={`w-full bg-ghost border border-rim rounded-xl px-4 py-3 text-sm text-white placeholder:text-muted focus:outline-none focus:border-purple/60 transition-colors ${extra ?? ""}`}
      />
    </div>
  )

  return (
    <div className="min-h-screen bg-void pt-16">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 py-10">
        {/* Back */}
        <button onClick={onBack} className="flex items-center gap-2 text-muted hover:text-white text-sm mb-8 transition-colors">
          <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
          Back to event
        </button>

        {/* Progress steps */}
        {event.hasReservedSeating && (
          <div className="flex items-center gap-3 mb-10">
            {["Choose Seats", "Payment"].map((label, i) => {
              const current = step === "seats" ? 0 : 1
              const done = i < current
              const active = i === current
              return (
                <div key={label} className="flex items-center gap-3">
                  <div className={`flex items-center gap-2 text-sm font-medium transition-colors ${active ? "text-white" : done ? "text-success" : "text-muted"}`}>
                    <span className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold ${active ? "gradient-btn text-white" : done ? "bg-success text-void" : "bg-ghost text-muted border border-rim"}`}>
                      {done ? "✓" : i + 1}
                    </span>
                    {label}
                  </div>
                  {i < 1 && <div className={`w-12 h-px ${done ? "bg-success" : "bg-rim"}`} />}
                </div>
              )
            })}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-5 gap-8">
          {/* Main area */}
          <div className="lg:col-span-3 space-y-6">
            {step === "seats" && (
              <div className="p-6 rounded-2xl bg-surface border border-rim">
                <h2 className="font-display text-xl font-semibold text-white mb-6">
                  Choose Your Seats
                  <span className="text-muted text-sm font-normal ml-2">({quantity} ticket{quantity > 1 ? "s" : ""})</span>
                </h2>
                <SeatMap quantity={quantity} onSeatsSelected={setSeats} />
                <button
                  onClick={() => setStep("payment")}
                  disabled={!seatsReady}
                  className={`mt-6 w-full py-3.5 rounded-xl font-semibold text-sm transition-all ${seatsReady ? "gradient-btn text-white" : "bg-ghost text-muted cursor-not-allowed border border-rim"}`}
                >
                  {seatsReady ? "Continue to Payment" : `Select ${quantity - seats.length} more seat${quantity - seats.length !== 1 ? "s" : ""}`}
                </button>
              </div>
            )}

            {step === "payment" && (
              <div className="p-6 rounded-2xl bg-surface border border-rim space-y-5">
                <h2 className="font-display text-xl font-semibold text-white">Payment Details</h2>

                {formField("Full Name", "name", "Jordan Lee")}
                {formField("Email Address", "email", "your@email.com")}

                <div className="border-t border-rim pt-5">
                  <p className="text-muted text-xs font-medium uppercase tracking-wide mb-4">Card Information</p>
                  {formField("Card Number", "card", "1234 5678 9012 3456")}
                  <div className="grid grid-cols-2 gap-4">
                    {formField("Expiry", "expiry", "MM / YY")}
                    {formField("CVV", "cvv", "•••")}
                  </div>
                </div>

                {/* Security badges */}
                <div className="flex items-center gap-4 text-muted text-xs py-2">
                  <span className="flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" className="w-4 h-4 text-success" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    SSL Secured
                  </span>
                  <span className="flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" className="w-4 h-4 text-success" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    PCI Compliant
                  </span>
                  <span className="flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" className="w-4 h-4 text-success" fill="none" stroke="currentColor" strokeWidth="2">
                      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Instant Confirmation
                  </span>
                </div>

                <button
                  onClick={handlePay}
                  disabled={processing}
                  className={`gradient-btn w-full py-4 rounded-xl text-white font-semibold text-sm tracking-wide transition-opacity ${processing ? "opacity-70 cursor-not-allowed" : ""}`}
                >
                  {processing ? (
                    <span className="flex items-center justify-center gap-2">
                      <svg className="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                      </svg>
                      Processing Payment…
                    </span>
                  ) : (
                    `Pay ${formatPrice(total)}`
                  )}
                </button>
              </div>
            )}
          </div>

          {/* Order summary */}
          <div className="lg:col-span-2">
            <div className="sticky top-24 rounded-2xl bg-surface border border-rim overflow-hidden">
              <div className="relative h-36 overflow-hidden bg-ghost">
                <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
                <div className="absolute inset-0 bg-gradient-to-t from-surface/90 to-transparent" />
              </div>
              <div className="p-5 space-y-4">
                <div>
                  <h3 className="font-display font-semibold text-white leading-snug">{event.title}</h3>
                  <p className="text-muted text-xs mt-1">{event.venue} · {event.city}</p>
                </div>

                <div className="space-y-2 text-sm border-t border-rim pt-4">
                  <div className="flex justify-between text-muted">
                    <span>{tier.name}</span>
                    <span className="text-white">{formatPrice(tier.price)}</span>
                  </div>
                  <div className="flex justify-between text-muted">
                    <span>Quantity</span>
                    <span className="text-white">× {quantity}</span>
                  </div>
                  <div className="flex justify-between text-muted">
                    <span>Subtotal</span>
                    <span className="text-white">{formatPrice(subtotal)}</span>
                  </div>
                  <div className="flex justify-between text-muted">
                    <span>Service fee (12%)</span>
                    <span className="text-white">{formatPrice(serviceFee)}</span>
                  </div>
                  <div className="flex justify-between font-bold text-white border-t border-rim pt-3">
                    <span>Total</span>
                    <span className="gradient-text text-lg">{formatPrice(total)}</span>
                  </div>
                </div>

                {seats.length > 0 && (
                  <div className="border-t border-rim pt-4">
                    <p className="text-muted text-xs uppercase tracking-wide mb-2">Selected Seats</p>
                    <div className="flex flex-wrap gap-1.5">
                      {seats.map((s) => (
                        <span key={s} className="px-2.5 py-1 rounded-full bg-purple/15 text-purple-glow text-xs border border-purple/25">
                          {s}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
