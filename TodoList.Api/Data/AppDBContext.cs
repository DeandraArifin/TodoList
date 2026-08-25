using Microsoft.EntityFrameworkCore;
using TodoList.Api.Models;

namespace TodoList.Api.Data
{
    public class AppDBContext : DbContext
    {
        public AppDBContext(DbContextOptions<AppDBContext> options) : base(options)
        {

        }

        public DbSet<TodoItem> TodoItems { get; set; }
        public DbSet<TodoListItem> TodoListItems { get; set; }
       
    }
}
