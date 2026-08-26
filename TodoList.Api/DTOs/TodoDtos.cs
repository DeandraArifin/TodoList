namespace TodoList.Api.DTOs;

public record TodoItemResponse(Guid Id, string Title, bool IsCompleted, Guid TodoListItemId);

public record TodoListResponse(Guid Id, string Title, IReadOnlyList<TodoItemResponse> Todos);

public record CreateTodoListRequest(string Title);

public record UpdateTodoListRequest(string Title);

public record CreateTodoItemRequest(string Title);

public record UpdateTodoItemRequest(string Title, bool IsCompleted);
