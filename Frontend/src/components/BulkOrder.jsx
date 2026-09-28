import { useState, useEffect } from "react";
import { FiBox, FiClock, FiCheckCircle } from "react-icons/fi";

const BulkOrder = () => {
  const [bulkOrders, setBulkOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = localStorage.getItem("user");

    if (storedUser) {
      const currentUser = JSON.parse(storedUser);
      // Dashboard se li gayi API URL for Bulk Quotes
      const apiUrl =
        "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/quotes/user_quotes_api.php";

      fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-API-KEY": "GatewayLinen@2026",
        },
        body: JSON.stringify({
          action: "get_user_quotes",
          userId: Number(
            currentUser.id || currentUser.UserId || currentUser.userId,
          ),
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            setBulkOrders(data.data || []);
          }
          setLoading(false);
        })
        .catch((err) => {
          console.error("Error fetching bulk orders:", err);
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
          <FiBox size={24} className="text-[#B58E58]" />
          <div>
            <h1 className="text-xl font-serif font-bold">My Bulk Orders</h1>
            <p className="text-xs text-gray-300 font-light mt-1">
              Track and manage your commercial wholesale orders.
            </p>
          </div>
        </div>

        <div className="p-6">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[#F7F2EB] text-[#031D44] text-[11px] uppercase tracking-wider font-bold">
                  <th className="p-4 rounded-tl-xl">Quote Number</th>
                  <th className="p-4">Date Submitted</th>
                  <th className="p-4">Items Summary</th>
                  <th className="p-4">Status</th>
                  <th className="p-4 rounded-tr-xl">Total Amount</th>
                </tr>
              </thead>
              <tbody className="text-sm">
                {loading ? (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-10 text-gray-400 text-sm"
                    >
                      Loading your bulk orders...
                    </td>
                  </tr>
                ) : bulkOrders.length > 0 ? (
                  bulkOrders.map((quote, index) => {
                    const status = (quote.status || "Pending").toLowerCase();
                    let statusColor = "bg-yellow-100 text-yellow-800";
                    if (status === "approved")
                      statusColor = "bg-green-100 text-green-800";
                    else if (status === "rejected")
                      statusColor = "bg-red-100 text-red-800";
                    else if (status === "converted")
                      statusColor = "bg-blue-100 text-blue-800";

                    return (
                      <tr
                        key={index}
                        className="border-b border-gray-100 hover:bg-[#FAF9F6] transition-colors"
                      >
                        <td className="p-4 font-bold text-[#031D44] truncate max-w-[150px]">
                          #{quote.quoteNumber}
                        </td>
                        <td className="p-4 text-gray-500 text-xs">
                          {quote.date}
                        </td>
                        <td className="p-4 text-gray-700 text-xs max-w-[200px] truncate">
                          Bulk Linen Items
                        </td>
                        <td className="p-4">
                          <span
                            className={`inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider ${statusColor}`}
                          >
                            {status === "approved" || status === "converted" ? (
                              <FiCheckCircle size={12} />
                            ) : (
                              <FiClock size={12} />
                            )}
                            {quote.status}
                          </span>
                        </td>
                        <td className="p-4 font-bold text-[#B58E58]">
                          CAD ${Number(quote.totalAmount).toFixed(2)}
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-10 text-gray-400 text-sm"
                    >
                      You haven't submitted any bulk quotes yet.
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

export default BulkOrder;
