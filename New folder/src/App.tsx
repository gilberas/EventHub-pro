import { useState } from "react"
import Navbar from "./components/Navbar"
import HomePage from "./components/HomePage"
import EventDetailPage from "./components/EventDetailPage"
import CheckoutPage from "./components/CheckoutPage"
import ConfirmationPage from "./components/ConfirmationPage"
import MyTicketsPage from "./components/MyTicketsPage"
import OrganizerDashboard from "./components/OrganizerDashboard"
import AdminDashboard from "./components/AdminDashboard"
import { EVENTS, type Event } from "./data/events"

type View = "home" | "event" | "checkout" | "confirmation" | "my-tickets" | "organizer" | "admin"
type UserRole = "customer" | "organizer" | "admin"

export default function App() {
  const [view, setView] = useState<View>("home")
  const [userRole, setUserRole] = useState<UserRole>("customer")
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null)
  const [selectedTierId, setSelectedTierId] = useState("")
  const [selectedQuantity, setSelectedQuantity] = useState(1)
  const [bookingId, setBookingId] = useState("")
  const [bookingSeats, setBookingSeats] = useState<string[]>([])

  const navigateTo = (v: View) => {
    setView(v)
    window.scrollTo({ top: 0, behavior: "smooth" })
  }

  const handleSelectEvent = (event: Event) => {
    setSelectedEvent(event)
    navigateTo("event")
  }

  const handleSelectEventById = (id: string) => {
    const event = EVENTS.find((e) => e.id === id)
    if (event) handleSelectEvent(event)
  }

  const handleCheckout = (tierId: string, quantity: number) => {
    setSelectedTierId(tierId)
    setSelectedQuantity(quantity)
    navigateTo("checkout")
  }

  const handleConfirm = (bId: string, seats: string[]) => {
    setBookingId(bId)
    setBookingSeats(seats)
    navigateTo("confirmation")
  }

  return (
    <div className="min-h-screen bg-void text-white">
      <Navbar
        currentView={view}
        userRole={userRole}
        onNavigate={navigateTo}
        onRoleChange={(role) => {
          setUserRole(role)
          navigateTo("home")
        }}
      />

      {view === "home" && <HomePage onSelectEvent={handleSelectEvent} />}

      {view === "event" && selectedEvent && (
        <EventDetailPage
          event={selectedEvent}
          onBack={() => navigateTo("home")}
          onCheckout={handleCheckout}
        />
      )}

      {view === "checkout" && selectedEvent && (
        <CheckoutPage
          event={selectedEvent}
          tierId={selectedTierId}
          quantity={selectedQuantity}
          onConfirm={handleConfirm}
          onBack={() => navigateTo("event")}
        />
      )}

      {view === "confirmation" && selectedEvent && (
        <ConfirmationPage
          event={selectedEvent}
          tierId={selectedTierId}
          quantity={selectedQuantity}
          bookingId={bookingId}
          seats={bookingSeats}
          onHome={() => navigateTo("home")}
          onMyTickets={() => navigateTo("my-tickets")}
        />
      )}

      {view === "my-tickets" && (
        <MyTicketsPage onSelectEvent={handleSelectEventById} />
      )}

      {view === "organizer" && <OrganizerDashboard />}

      {view === "admin" && <AdminDashboard />}
    </div>
  )
}
