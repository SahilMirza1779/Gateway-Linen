import { useState, useEffect } from "react";
import { FiShoppingBag, FiTruck, FiCheck } from "react-icons/fi";

const OrderHistory = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = localStorage.getItem("user");

    if (storedUser) {
      const currentUser = JSON.parse(storedUser);
      // Dashboard se li gayi API URL for Standard Orders
      const apiUrl =
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/orders/user_orders_api.php";

      fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-API-KEY": "GatewayLinen@2026",
        },
        body: JSON.stringify({
          action: "get_user_orders",
          userId: Number(
            currentUser.id || currentUser.UserId || currentUser.userId,
          ),
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            setOrders(data.data || []);
          }
          setLoading(false);
        })
        .catch((err) => {
          console.error("Error fetching order history:", err);
          setLoading(false);
        });
    } else {
      setTimeout(() => {
        setLoading(false);
      }, 0);
    }
  }, []);

  return (
    <div className="min-h-screen bg-[#F0EAE1] py-10 px-4 md:px-10 font-sans">
      <div className="max-w-5xl mx-auto bg-white rounded-[24px] shadow-xl border border-[#E5DCD0] overflow-hidden">
        <div className="bg-[#031D44] p-6 text-white flex items-center gap-3">
          <FiShoppingBag size={24} className="text-[#B58E58]" />
          <div>
            <h1 className="text-xl font-serif font-bold">
              Standard Order History
            </h1>
            <p className="text-xs text-gray-300 font-light mt-1">
              Review your past retail purchases and tracking details.
            </p>
          </div>
        </div>

        <div className="p-6">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[#F7F2EB] text-[#031D44] text-[11px] uppercase tracking-wider font-bold">
                  <th className="p-4 rounded-tl-xl">Order Number</th>
                  <th className="p-4">Date Placed</th>
                  <th className="p-4">Payment Method</th>
                  <th className="p-4">Status</th>
                  <th className="p-4 rounded-tr-xl">Total Paid</th>
                </tr>
              </thead>
              <tbody className="text-sm">
                {loading ? (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-10 text-gray-400 text-sm"
                    >
                      Loading your order history...
                    </td>
                  </tr>
                ) : orders.length > 0 ? (
                  orders.map((order, index) => (
                    <tr
                      key={index}
                      className="border-b border-gray-100 hover:bg-[#FAF9F6] transition-colors"
                    >
                      <td className="p-4 font-bold text-[#031D44]">
                        #{order.id}
                      </td>
                      <td className="p-4 text-gray-500 text-xs">
                        {order.date}
                      </td>
                      <td className="p-4 text-gray-700 text-xs">
                        {order.paymentMethod || "Standard Payment"}
                      </td>
                      <td className="p-4">
                        <span
                          className={`inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider ${
                            (order.status || "").toLowerCase() === "delivered"
                              ? "bg-green-100 text-green-700"
                              : "bg-blue-100 text-blue-700"
                          }`}
                        >
                          {(order.status || "").toLowerCase() ===
                          "delivered" ? (
                            <FiCheck size={12} />
                          ) : (
                            <FiTruck size={12} />
                          )}
                          {order.status || "Processing"}
                        </span>
                      </td>
                      <td className="p-4 font-bold text-[#B58E58]">
                        CAD ${order.total}
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-10 text-gray-400 text-sm"
                    >
                      You haven't placed any standard orders yet.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrderHistory;
